<?php

namespace ProxyTests;

use PHPUnit\Framework\TestCase;
use Proxy\HTML;

class HTMLTest extends TestCase
{
    public function testBlockLinks()
    {
        $htmlWithLinks = file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, 'fixtures', 'withLinks.html']));
        $html = new HTML($htmlWithLinks);
        $htmlBlockLinks = file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, 'fixtures', 'blockLinks.html']));
        $this->assertEquals($htmlBlockLinks, $html->blockLinks()->html());
    }

    public function testInsertJS()
    {
        $htmlWithoutJS = file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, 'fixtures', 'withLinks.html']));
        $html = new HTML($htmlWithoutJS);
        $htmlWithInsertedJS = file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, 'fixtures', 'insertedJS.html']));
        $this->assertEquals($htmlWithInsertedJS, $html->insertJS('test.js')->html());
    }
}
