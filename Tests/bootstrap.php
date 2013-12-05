<?php

$file = __DIR__.'/../vendor/autoload.php';
if (!file_exists($file)) {
    throw new RuntimeException('Install dependencies to run test suite.');
}


/** @var $autoload \Composer\Autoload\ClassLoader */
$autoload = require_once $file;

$autoload->add('', realpath(__DIR__ . '/../../../'));
