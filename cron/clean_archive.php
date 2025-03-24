<?php

$archiveDir = __DIR__ . '/../storage/archive/';
$now = time();

foreach (glob($archiveDir . '*') as $file) {
    if (is_file($file) && filemtime($file) < strtotime('-90 days')) {
        unlink($file);
    }
}