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
        $this->assertEquals($htmlBlockLinks, $htmlModifier->blockLinks()->saveHtml());
    }

    public function testInsertJS()
    {
        $htmlWithoutJS = '<html><body></body></html>';
        $htmlModifier = new HTMLModifier($htmlWithoutJS);
        $source = 'test.js';
        $htmlWithOneJS = $htmlModifier->insertJS($source)->saveHtml();
        $this->assertContains('<script src="test.js"></script>', $htmlWithOneJS);

        $htmlWithoutJS = '<html><body></body></html>';
        $htmlModifier = new HTMLModifier($htmlWithoutJS);
        $sources = ['test1.js', 'test2.js'];
        $htmlWithTwoJS = $htmlModifier->insertJS($sources)->saveHtml();
        $this->assertContains('<script src="test1.js"></script>', $htmlWithTwoJS);
        $this->assertContains('<script src="test2.js"></script>', $htmlWithTwoJS);
    }

    public function testDeleteBaseTag()
    {
        $htmlWithBase = '<html><base href="http://test.com"></html>';
        $htmlModifier = new HTMLModifier($htmlWithBase);
        $this->assertNotContains('base', $htmlModifier->deleteBaseTag()->saveHtml());
    }

    public function testBlackList()
    {
        $htmlWithUnwantedArgument = '<html><head><link href="test1"><base href="http://test2.com"></head></html>';
        $htmlModifier = new HTMLModifier($htmlWithUnwantedArgument);
        $blacklist = ['test1'];
        $htmlWithDeletedTags = $htmlModifier->setBlacklist($blacklist)->saveHtml();
        $this->assertNotContains('link', $htmlWithDeletedTags);

        $htmlWithUnwantedArguments = '<html><head><link id="test1" href="test1"><base href="test2"></head></html>';
        $htmlModifier = new HTMLModifier($htmlWithUnwantedArguments);
        $blacklist = ['test1', 'test2'];
        $htmlWithDeletedTags = $htmlModifier->setBlacklist($blacklist)->saveHtml();
        $this->assertNotContains('link', $htmlWithDeletedTags);
        $this->assertNotContains('base', $htmlWithDeletedTags);
    }
}
