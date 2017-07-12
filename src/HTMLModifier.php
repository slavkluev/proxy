<?php

namespace Proxy;

use DOMDocument;

class HTMLModifier
{
    private $dom;

    public function __construct($html)
    {
        $this->dom = new DOMDocument;
        @$this->dom->loadHTML($html);
    }

    public function blockLinks()
    {
        foreach ($this->dom->getElementsByTagName('a') as $link) {
            $link->setAttribute('href', '#');
        }
        return $this;
    }

    public function insertJS($sources)
    {
        if (!is_array($sources)) {
            $sources = [$sources];
        }
        $body = $this->dom->getElementsByTagName('body')->item(0);
        foreach ($sources as $source) {
            $node = $this->dom->createElement("script");
            $node->setAttribute("src", $source);
            $body->appendChild($node);
        }
        return $this;
    }

    public function deleteBaseTag()
    {
        $bases = $this->dom->getElementsByTagName('base');
        if ($bases->length > 0) {
            $base = $bases->item(0);
            $base->parentNode->removeChild($base);
        }
        return $this;
    }

    public function setBlacklist($keys)
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

        $tags = $this->dom->getElementsByTagName('*');
        $nodesToRemove = [];
        foreach ($tags as $tag) {
            if ($isRemoved($tag)) {
                $nodesToRemove[] = $tag;
            }
        }
        foreach ($nodesToRemove as $domElement) {
            $domElement->parentNode->removeChild($domElement);
        }
        return $this;
    }

    public function saveHtml()
    {
        return $this->dom->saveHTML();
    }
}
