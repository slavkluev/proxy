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
        $this->assertFileNotExists($fs->path('/' . md5($url) . '.html'));
        $this->assertContains('html', $proxy->proxifySite($url));
        $this->assertFileExists($fs->path('/' . md5($url) . '.html'));
    }

    public function testBlockLinks()
    {
        $htmlWithLinks = file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, 'fixtures', 'withLinks.html']));
        $htmlBlockLinks = file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, 'fixtures', 'blockLinks.html']));
        $this->assertEquals($htmlBlockLinks, $this->proxy->blockLinks($htmlWithLinks));
    }

    public function testInsertJS()
    {
        $html = file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, 'fixtures', 'withLinks.html']));
        $htmlWithInsertedJS = file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, 'fixtures', 'insertedJS.html']));
        $this->assertEquals($htmlWithInsertedJS, $this->proxy->insertJS($html, 'test.js'));
    }

    public function testConvertFileToBase64()
    {
        $image = file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, 'fixtures', 'img', '1.png']));
        $convertedImage = file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, 'fixtures', 'img', '1.base64']));
        $this->assertEquals($convertedImage, $this->proxy->convertFileToBase64($image));
    }

    public function testReplaceRelativeUrlsToAbsolute()
    {
        $css = file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, 'fixtures', 'css', 'css.css']));
        $replacedCss = $this->proxy->replaceRelativeUrlsToAbsolute($css, 'https://www.google.com/abc');
        $this->assertContains('https://www.google.com/img/1.png', $replacedCss);
    }

    public function testGetFileUrls()
    {
        $css = file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, 'fixtures', 'css', 'css.css']));
        $fileUrls = $this->proxy->getFileUrls($css);
        $this->assertEquals(['../img/1.png'], $fileUrls);
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
