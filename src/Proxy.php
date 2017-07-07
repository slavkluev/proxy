<?php

namespace Proxy;

use DOMDocument;
use finfo;
use Proxy\Exceptions\DownloadException;
use webignition\AbsoluteUrlDeriver\AbsoluteUrlDeriver;

class Proxy
{
    private $downloadDirectory = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'sites';

    public function proxifySite($url)
    {
        $html = $this->downloadHTML($url);
        $html = $this->insertConvertedCSSInHtml($html, $url);
        $html = $this->convertFiles($html, $url);
        $html = $this->blockLinks($html);

        $filename = $this->save($html, $url);

        return $filename;
    }

    public function downloadHTML($url)
    {
        $html = $this->download($url);
        return $html;
    }

    public function insertConvertedCSSInHtml($html, $baseUrl)
    {
        $xml = new DOMDocument;
        @$xml->loadHTML($html);
        $nodesToRemove = [];
        foreach ($xml->getElementsByTagName('link') as $link) {
            if ($link->getAttribute('rel') == 'stylesheet') {
                $nodesToRemove[] = $link;
                $href = $link->getAttribute('href');
                $css = $this->download($this->absoluteUrl($href, $baseUrl));
                $convertedCss = $this->convertFiles($css, $this->absoluteUrl($href, $baseUrl));
                $node = $xml->createElement("style", $convertedCss);
                $node->setAttribute("type", "text/css");
                $link->parentNode->appendChild($node);
            }
        }
        foreach ($nodesToRemove as $domElement) {
            $domElement->parentNode->removeChild($domElement);
        }
        $html = $xml->saveHTML();
        return $html;
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

    public function convertFiles($text, $baseUrl)
    {
        $tags = ['link', 'script', 'img', 'meta'];
        $attributes = ['src', 'href', 'content'];
        $types = ['jpe?g', 'gif', 'png', 'svg', 'eot', 'woff2?', 'ttf', 'ico'];
        $text = preg_replace_callback(
            "~((?:url\(|<(?:"
            . implode('|', $tags)
            . ")[^>]+(?:"
            . implode('|', $attributes)
            . ")\s*=\s*)(?!['\"]?(?:data))['\"]?)([^'\"\)\s>]+(?:"
            . implode('|', $types)
            . "))~",
            function ($matches) use ($baseUrl) {
                try {
                    $data = $this->download($this->absoluteUrl($matches[2], $baseUrl));
                } catch (DownloadException $e) {
                    $data = $this->absoluteUrl($matches[2], $baseUrl);
                }
                return $matches[1] . $this->convertFileToBase64($data);
            },
            $text
        );
        return $text;
    }

    public function convertFileToBase64($data)
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($data);
        $base64 = 'data:' . $mime . ';base64,' . base64_encode($data);
        return $base64;
    }

    public function getFileUrls($text)
    {
        $tags = ['link', 'script', 'img', 'meta'];
        $attributes = ['src', 'href', 'content'];
        $types = ['jpe?g', 'gif', 'png', 'svg', 'eot', 'woff2?', 'ttf', 'ico'];
        preg_match_all(
            "~((?:url\(|<(?:"
            . implode('|', $tags)
            . ")[^>]+(?:"
            . implode('|', $attributes)
            . ")\s*=\s*)(?!['\"]?(?:data))['\"]?)([^'\"\)\s>]+\.(?:"
            . implode('|', $types)
            . "))~",
            $text,
            $matches,
            PREG_PATTERN_ORDER
        );
        $urls = array_unique($matches[2]);
        return $urls;
    }

    public function absoluteUrl($relativeUrl, $baseUrl)
    {
        $deriver = new AbsoluteUrlDeriver($relativeUrl, $baseUrl);
        $absoluteUrl = (string)$deriver->getAbsoluteUrl();
        return $absoluteUrl;
    }

    private function save($html, $url)
    {
        $filename = parse_url($url, PHP_URL_HOST) . '.html';
        file_put_contents($this->downloadDirectory . DIRECTORY_SEPARATOR . $filename, $html);
        return $filename;
    }

    private function download($url)
    {
        try {
            $result = file_get_contents($url);
            return $result;
        } catch (\Exception $e) {
            throw new DownloadException();
        }
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
