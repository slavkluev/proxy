<?php

require_once __DIR__ . '/../vendor/autoload.php';

$url = htmlspecialchars($_GET["url"]);

$proxy = new \Proxy\Proxy();
$html = $proxy->proxifySite($url);

$htmlChanger = new \Proxy\HTMLModifier($html);
$modifiedHTML = $htmlChanger
    ->blockLinks()
    ->insertJS('test.js')
    ->html();

echo $modifiedHTML;