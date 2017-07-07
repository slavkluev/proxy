<?php

$downloadDirectory = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'sites';
$period = 86400;

if (is_dir($downloadDirectory)) {
    foreach (new DirectoryIterator($downloadDirectory) as $fileInfo) {
        if($fileInfo->isFile() && (time() - filectime($fileInfo->getRealPath())) > $period) {
            unlink($fileInfo->getRealPath());
        }
    }
}
