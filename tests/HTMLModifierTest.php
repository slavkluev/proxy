<?php

namespace ProxyTests;

use PHPUnit\Framework\TestCase;
use Proxy\HTMLModifier;

class HTMLModifierTest extends TestCase
{
    public function testBlockLinks()
    {
        $htmlWithLinks = file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, 'fixtures', 'withLinks.html']));
        $htmlModifier = new HTMLModifier($htmlWithLinks);
        $htmlBlockLinks = file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, 'fixtures', 'blockLinks.html']));
        $this->assertEquals($htmlBlockLinks, $htmlModifier->blockLinks()->html());
    }

    public function testInsertJS()
    {
        $htmlWithoutJS = file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, 'fixtures', 'withLinks.html']));
        $htmlModifier = new HTMLModifier($htmlWithoutJS);
        $htmlWithInsertedJS = file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, 'fixtures', 'insertedJS.html']));
        $this->assertEquals($htmlWithInsertedJS, $htmlModifier->insertJS('test.js')->html());
    }

    public function testDeleteBaseTag()
    {
        $htmlWithBase = '<html><base href="http://test.com"></html>';
        $htmlModifier = new HTMLModifier($htmlWithBase);
        $this->assertNotContains('base', $htmlModifier->deleteBaseTag()->html());
    }

    public function testBlackList()
    {
        $htmlWithUnwantedArgument = '<html><head><link href="test1"><base href="http://test2.com"></head><body></body></html>';
        $htmlModifier = new HTMLModifier($htmlWithUnwantedArgument);
        $this->assertNotContains('link', $htmlModifier->setBlackList(['test1'])->html());

        $htmlWithUnwantedArguments = '<html><head><link id="test1" href="test1"><base href="http://test2.com"></head><body></body></html>';
        $htmlModifier = new HTMLModifier($htmlWithUnwantedArguments);
        $this->assertNotContains('link', $htmlModifier->setBlackList(['test1', 'test2'])->html());
        $this->assertNotContains('base', $htmlModifier->setBlackList(['test1', 'test2'])->html());
    }
}
