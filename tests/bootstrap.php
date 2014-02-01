<?php

define('TEST_RESOURCES_DIR', __DIR__.'/PHPPdf/Resources');

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__.'/../lib/PHPPdf/Autoloader.php';
PHPPdf\Autoloader::register();
PHPPdf\Autoloader::register(__DIR__.'/../lib/vendor/Zend/library');
PHPPdf\Autoloader::register(__DIR__.'/../lib/vendor/ZendPdf/library');
PHPPdf\Autoloader::register(__DIR__.'/../lib/vendor/Imagine/lib');
PHPPdf\Autoloader::register(__DIR__.'/');
