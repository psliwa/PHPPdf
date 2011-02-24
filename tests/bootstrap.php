<?php

set_include_path(dirname(__FILE__).'/../lib' . PATH_SEPARATOR. dirname(__FILE__).'/../lib/vendor' . PATH_SEPARATOR . dirname(__FILE__).'/../lib/vendor/Zend' . PATH_SEPARATOR . get_include_path());

require_once 'PHPPdf/Autoloader.php';
PHPPdf\Autoloader::register();
PHPPdf\Autoloader::register(dirname(__FILE__).'/../lib/vendor');

require_once __DIR__.'/TestCase.php';