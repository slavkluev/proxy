<?php

namespace Proxy;

use DirectoryIterator;
use DOMDocument;
use finfo;
use MCurl\Client;
use Proxy\Exceptions\DirectoryNotFoundException;
use Proxy\Exceptions\DownloadException;
use webignition\AbsoluteUrlDeriver\AbsoluteUrlDeriver;

class Proxy
{
    private $downloadDirectory = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'sites';
    private $cleaningPeriod = 86400;

    public function proxifySite($url)
    {
        $this->clean();

        $filename = $this->downloadDirectory . DIRECTORY_SEPARATOR . md5($url) . '.html';
        if (is_file($filename)) {
            return file_get_contents($filename);
        }

        $html = $this->download($url);
        $html = $this->insertCSSIntoHtml($html, $url);
        $html = $this->convertFiles($html, $url);
        $html = $this->replaceJSRelativeUrls($html, $url);

        $this->save($html, $filename);

        return file_get_contents($filename);
    }

    public function insertCSSIntoHtml($html, $baseUrl)
    {
        $xml = new DOMDocument;
        @$xml->loadHTML($html);
        $nodesToRemove = [];
        foreach ($xml->getElementsByTagName('link') as $link) {
            if ($link->getAttribute('rel') == 'stylesheet') {
                $nodesToRemove[] = $link;
                $href = $link->getAttribute('href');
                $css = $this->download($this->absoluteUrl($href, $baseUrl));
                $css = $this->replaceRelativeUrlsToAbsolute($css, $this->absoluteUrl($href, $baseUrl));
                $node = $xml->createElement("style", $css);
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

    public function replaceJSRelativeUrls($html, $baseUrl)
    {
        $xml = new DOMDocument;
        @$xml->loadHTML($html);
        foreach ($xml->getElementsByTagName('script') as $script) {
            if ($script->hasAttribute('src')) {
                $src = $script->getAttribute('src');
                $script->setAttribute('src', $this->absoluteUrl($src, $baseUrl));
            }
        }
        $html = $xml->saveHTML();
        return $html;
    }

    public function convertFiles($text, $baseUrl)
    {
        $textWithAbsoluteUrls = $this->replaceRelativeUrlsToAbsolute($text, $baseUrl);
        $urls = $this->getFileUrls($textWithAbsoluteUrls);
        $files = $this->download($urls);
        foreach ($urls as $url) {
            $convertedFile = $this->convertFileToBase64($files[$url]);
            $textWithAbsoluteUrls = str_replace($url, $convertedFile, $textWithAbsoluteUrls);
        }
        return $textWithAbsoluteUrls;
    }

    public function convertFileToBase64($data)
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($data);
        $base64 = 'data:' . $mime . ';base64,' . base64_encode($data);
        return $base64;
    }

    public function replaceRelativeUrlsToAbsolute($text, $baseUrl)
    {
        preg_match('/<base[^>]+href\s*=\s*[\'"]?([^\'"\)\s>]+)/', $text, $matches);
        if (!empty($matches[1])) {
            $baseUrl = $matches[1];
        }

        $urls = $this->getFileUrls($text);
        $absoluteUrls = array_map(function ($url) use ($baseUrl) {
            return $this->absoluteUrl($url, $baseUrl);
        }, $urls);
        $replacedText = str_replace($urls, $absoluteUrls, $text);
        return $replacedText;
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
        $absoluteUrl = urldecode((string)$deriver->getAbsoluteUrl());
        return $absoluteUrl;
    }

    private function save($html, $filename)
    {
        if (!is_dir($this->downloadDirectory)) {
            throw new DirectoryNotFoundException();
        }
        file_put_contents($filename, $html);
    }

    private function download($urls)
    {
        $client = new Client();
        $clearResults = [];
        try {
            $results = $client->get($urls);
            if (!is_array($urls)) {
                return $results;
            }
            foreach ($results as $result) {
                $clearResults[$result->getInfo()['url']] = $result->getBody();
            }
            return $clearResults;
        } catch (\Exception $e) {
            throw new DownloadException();
        }
    }

    private function clean()
    {
        if (!is_dir($this->downloadDirectory)) {
            throw new DirectoryNotFoundException();
        }
        foreach (new DirectoryIterator($this->downloadDirectory) as $fileInfo) {
            if ($fileInfo->isFile() && (time() - filectime($fileInfo->getRealPath())) > $this->cleaningPeriod) {
                unlink($fileInfo->getRealPath());
            }
        }
    }

    public function getDownloadDirectory()
    {
        return $this->downloadDirectory;
    }

    public function setDownloadDirectory($dir)
    {
        if (!is_dir($dir)) {
            throw new DirectoryNotFoundException();
        }
        $this->downloadDirectory = $dir;
        return $this;
    }

    public function getCleaningPeriod()
    {
        return $this->cleaningPeriod;
    }

    public function setCleaningPeriod($timestamp)
    {
        $this->cleaningPeriod = $timestamp;
        return $this;
    }
}
