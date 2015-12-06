<?php

define('TEST_RESOURCES_DIR', __DIR__.'/PHPPdf/Resources');

error_reporting(E_ALL);
ini_set('display_errors', 1);

$loader = require __DIR__.'/../vendor/autoload.php';

$loader->addPsr4('PHPPdf\\', __DIR__.'/PHPPdf');
