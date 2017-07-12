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

    public function insertJS($sources)
    {
        if (!is_array($sources)) {
            $sources = [$sources];
        }
        $dom = $this->getDocument();
        $body = $dom->getElementsByTagName('body')->item(0);
        foreach ($sources as $source) {
            $node = $dom->createElement("script");
            $node->setAttribute("src", $source);
            $body->appendChild($node);
        }
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

    public function setBlackList($keys)
    {
        $isRemoved = function ($tag) use ($keys) {
            if ($tag->hasAttributes()) {
                foreach ($tag->attributes as $attribute) {
                    foreach ($keys as $key) {
                        if (strrpos($attribute->value, $key) !== false) {
                            return true;
                        }
                    }
                }
            }
            return false;
        };

        $dom = $this->getDocument();
        $tags = $dom->getElementsByTagName('*');
        $nodesToRemove = [];
        foreach ($tags as $tag) {
            if ($isRemoved($tag)) {
                $nodesToRemove[] = $tag;
            }
        }
        foreach ($nodesToRemove as $domElement) {
            $domElement->parentNode->removeChild($domElement);
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
