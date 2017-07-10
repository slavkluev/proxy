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
        $dom = $this->getDocument();
        foreach ($dom->getElementsByTagName('a') as $link) {
            $link->setAttribute('href', '#');
        }
        $this->html = $dom->saveHTML();
        return $this;
    }

    public function insertJS($src)
    {
        $dom = $this->getDocument();
        $body = $dom->getElementsByTagName('body')->item(0);
        $node = $dom->createElement("script");
        $node->setAttribute("src", $src);
        $body->appendChild($node);
        $this->html = $dom->saveHTML();
        return $this;
    }

    public function deleteBaseTag()
    {
        $dom = $this->getDocument();
        $bases = $dom->getElementsByTagName('base');
        if ($bases->length > 0) {
            $base = $bases->item(0);
            $base->parentNode->removeChild($base);
        }
        $this->html = $dom->saveHTML();
        return $this;
    }

    public function html()
    {
        return $this->html;
    }

    private function getDocument()
    {
        $dom = new DOMDocument;
        @$dom->loadHTML($this->html);
        return $dom;
    }
}
