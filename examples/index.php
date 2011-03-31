<?php

error_reporting(E_ALL | E_NOTICE);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

set_include_path(dirname(__FILE__).'/../lib' . PATH_SEPARATOR. dirname(__FILE__).'/../lib/vendor' . PATH_SEPARATOR . dirname(__FILE__).'/../lib/vendor/Zend' . PATH_SEPARATOR . get_include_path());
mb_internal_encoding('utf-8');

require_once 'PHPPdf/Autoloader.php';

PHPPdf\Autoloader::register();
PHPPdf\Autoloader::register(dirname(__FILE__).'/../lib/vendor');

$facade = PHPPdf\Parser\FacadeBuilder::create()->setCache('File', array('cache_dir' => __DIR__.'/cache/'))
                                               ->setUseCacheForStylesheetConstraint(true)
                                               ->build();

if(!isset($_GET['name']))
{
    die('Pass example name by "name" parameter.');
}

$name = basename($_GET['name']);

$documentFilename = './'.$name.'.xml';
$stylesheetFilename = './'.$name.'-style.xml';

if(!is_readable($documentFilename) || !is_readable($stylesheetFilename))
{
    die(sprintf('Example "%s" dosn\'t exist.', $name));
}

$xml = str_replace('dir:', __DIR__.'/', file_get_contents($documentFilename));
$stylesheet = PHPPdf\Util\DataSource::fromFile($stylesheetFilename);

$content = $facade->render($xml, $stylesheet);

header('Content-Type: application/pdf');
echo $content;
