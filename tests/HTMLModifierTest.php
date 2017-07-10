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
}
