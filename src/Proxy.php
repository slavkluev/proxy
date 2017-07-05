<?php

namespace Proxy;

use DOMDocument;
use Sabberworm\CSS\Parser;
use Sabberworm\CSS\Value\CSSString;
use Sabberworm\CSS\Value\URL;
use webignition\AbsoluteUrlDeriver\AbsoluteUrlDeriver;

class Proxy
{
    private $downloadDirectory = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'sites';

    public function proxifySite($url)
    {
        $html = $this->downloadHTML($url);
        $css = $this->downloadCSS($url);
        $images = $this->downloadImages($url);

        $html = $this->blockLinks($html);

        $convertedCSS = [];
        foreach ($css as $link => $data) {
            $convertedCSS[$link] = $this->convertCSSImages($data, $this->absoluteUrl($link, $url));
        }

        list($html, $convertedCSS, $images) = $this->renamePaths($html, $convertedCSS, $images);
        $path = $this->downloadDirectory . DIRECTORY_SEPARATOR . parse_url($url, PHP_URL_HOST);
        $this->save($html, $convertedCSS, $images, $path);

        return $path;
    }

    public function downloadHTML($url)
    {
        $html = $this->download($url);
        return $html;
    }

    public function downloadCSS($url)
    {
        $css = [];
        $xml = new DOMDocument;
        @$xml->loadHTML($this->downloadHTML($url));
        foreach ($xml->getElementsByTagName('link') as $link) {
            if ($link->getAttribute('rel') == 'stylesheet') {
                $href = $link->getAttribute('href');
                $css[$href] = $this->download($this->absoluteUrl($href, $url));
            }
        }
        return $css;
    }

    public function downloadImages($url)
    {
        $images = [];
        $dom = new DOMDocument;
        @$dom->loadHTML($this->downloadHTML($url));
        foreach ($dom->getElementsByTagName('img') as $image) {
            $src = $image->getAttribute('src');
            $images[$src] = $this->download($this->absoluteUrl($src, $url));
        }
        return $images;
    }

    public function blockLinks($html)
    {
        $xml = new DOMDocument;
        @$xml->loadHTML($html);
        foreach ($xml->getElementsByTagName('a') as $link) {
            $link->setAttribute('href', '#');
        }
        $html = $xml->saveHTML();
        return $html;
    }

    public function convertCSSImages($css, $baseUrl)
    {
        $parser = new Parser($css);
        $document = $parser->parse();
        $types = ['jpg', 'png', 'gif'];
        foreach ($document->getAllValues() as $value) {
            if ($value instanceof URL) {
                $absoluteUrl = $this->absoluteUrl($value->getURL()->getString(), $baseUrl);
                $type = pathinfo($absoluteUrl, PATHINFO_EXTENSION);
                if (in_array($type, $types)) {
                    $data = $this->download($absoluteUrl);
                    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                    $value->setURL(new CSSString($base64));
                }
            }
        }
        $css = $document->render();
        return $css;
    }

    public function renamePaths($html, $css, $images)
    {
        $rename = function ($array, $prefix) use (&$html) {
            $result = [];
            foreach ($array as $fileName => $data) {
                $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                $newFileName = $prefix . uniqid() . '.' . $extension;
                $html = str_replace($fileName, $newFileName, $html);
                $result[$newFileName] = $data;
            }
            return $result;
        };

        $newCSS = $rename($css, 'css/');
        $newImages = $rename($images, 'img/');

        return [$html, $newCSS, $newImages];
    }

    public function absoluteUrl($relativeUrl, $baseUrl)
    {
        $deriver = new AbsoluteUrlDeriver($relativeUrl, $baseUrl);
        $absoluteUrl = (string)$deriver->getAbsoluteUrl();
        return $absoluteUrl;
    }

    private function save($html, $css, $images, $path)
    {
        $makeDirectory = function ($path) {
            if (!is_dir($path)) {
                mkdir($path);
            }
        };
        $makeDirectory($path);
        $makeDirectory($path . DIRECTORY_SEPARATOR . 'css');
        $makeDirectory($path . DIRECTORY_SEPARATOR . 'img');

        file_put_contents($path . DIRECTORY_SEPARATOR . 'index.html', $html);
        foreach ($css as $relativePath => $data) {
            file_put_contents($path . DIRECTORY_SEPARATOR . $relativePath, $data);
        }
        foreach ($images as $relativePath => $data) {
            file_put_contents($path . DIRECTORY_SEPARATOR . $relativePath, $data);
        }
    }

    private function download($url)
    {
        $result = file_get_contents($url);
        return $result;
    }

    public function getDownloadDirectory()
    {
        return $this->downloadDirectory;
    }

    public function setDownloadDirectory($dir)
    {
        $this->downloadDirectory = $dir;
    }
}
