<?php

namespace ProxyTests;

use Proxy\Proxy;
use PHPUnit\Framework\TestCase;
use VirtualFileSystem\FileSystem;

class ProxyTest extends TestCase
{
    private $proxy;

    public function setUp()
    {
        $this->proxy = new Proxy();
    }

    public function testProxifySite()
    {
        $url = 'https://google.com';
        $fs = new FileSystem();
        $proxy = new Proxy();
        $proxy->setDownloadDirectory($fs->path('/'));
        $proxy->proxifySite($url);
        $this->assertDirectoryExists($fs->path('/' . parse_url($url, PHP_URL_HOST)));
        $this->assertFileExists($fs->path('/' . parse_url($url, PHP_URL_HOST)) . '/index.html');
    }

    public function testDownloadCSS()
    {
        $url = 'https://google.com';
        $this->assertNotNull($this->proxy->downloadCSS($url));
    }

    public function testDownloadImages()
    {
        $url = 'https://google.com';
        $this->assertNotNull($this->proxy->downloadImages($url));
    }

    public function testBlockLinks()
    {
        $htmlWithLinks = file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, 'fixtures', 'withLinks.html']));
        $htmlBlockLinks = file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, 'fixtures', 'blockLinks.html']));
        $this->assertEquals($htmlBlockLinks, $this->proxy->blockLinks($htmlWithLinks));
    }

    public function testConvertCSSImages()
    {
        $basePath = implode(DIRECTORY_SEPARATOR, [__DIR__, 'fixtures', 'css', 'css.css']);
        $css = file_get_contents($basePath);
        $convertedCss = file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, 'fixtures', 'css', 'converted.css']));
        $this->assertEquals($convertedCss, $this->proxy->convertCSSImages($css, $basePath));
    }

    public function testAbsoluteUrl()
    {
        $relativeUrl = '../test.img';
        $baseUrl = 'http://test.com/test/test.php';
        $this->assertEquals('http://test.com/test.img', $this->proxy->absoluteUrl($relativeUrl, $baseUrl));

        $relativeUrl = '../test.img';
        $baseUrl = 'http://test.com/test/';
        $this->assertEquals('http://test.com/test.img', $this->proxy->absoluteUrl($relativeUrl, $baseUrl));

        $relativeUrl = '/test.img';
        $baseUrl = 'http://test.com/test/test2/';
        $this->assertEquals('http://test.com/test.img', $this->proxy->absoluteUrl($relativeUrl, $baseUrl));

        $relativeUrl = 'http://test2.com/test.img';
        $baseUrl = 'http://test.com/test/test2/';
        $this->assertEquals('http://test2.com/test.img', $this->proxy->absoluteUrl($relativeUrl, $baseUrl));

        $relativeUrl = '//test2.com/test.img';
        $baseUrl = 'http://test.com/test/test2/';
        $this->assertEquals('http://test2.com/test.img', $this->proxy->absoluteUrl($relativeUrl, $baseUrl));
    }
}
