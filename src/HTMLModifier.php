<?php

namespace Proxy;

use DOMDocument;

class HTMLModifier
{
    private $html;

    public function __construct($html)
    {
        $this->html = $html;
    }

    public function blockLinks()
    {
        $dom = new DOMDocument;
        @$dom->loadHTML($this->html);
        foreach ($dom->getElementsByTagName('a') as $link) {
            $link->setAttribute('href', '#');
        }
        $this->html = $dom->saveHTML();
        return $this;
    }

    public function insertJS($src)
    {
        $dom = new DOMDocument;
        @$dom->loadHTML($this->html);
        $body = $dom->getElementsByTagName('body')->item(0);
        $node = $dom->createElement("script");
        $node->setAttribute("src", $src);
        $body->appendChild($node);
        $this->html = $dom->saveHTML();
        return $this;
    }

    public function html()
    {
        return $this->html;
    }
}
