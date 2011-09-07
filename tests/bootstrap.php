<?php

define('TEST_RESOURCES_DIR', __DIR__.'/PHPPdf/Resources');

require_once __DIR__.'/../lib/PHPPdf/Autoloader.php';
PHPPdf\Autoloader::register();
PHPPdf\Autoloader::register(__DIR__.'/../lib/vendor');
PHPPdf\Autoloader::register(__DIR__.'/');
