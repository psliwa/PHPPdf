<?php

error_reporting(E_ALL | E_NOTICE);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

set_time_limit(240);

require_once __DIR__.'/get_examples.php';
require_once __DIR__.'/../vendor/autoload.php';

PHPPdf\Autoloader::register();
PHPPdf\Autoloader::register(__DIR__.'/../lib/vendor/Zend/library');

// set different way of configuration
//$facade = PHPPdf\Core\FacadeBuilder::create(new PHPPdf\Core\Configuration\DependencyInjection\LoaderImpl())->setCache('File', array('cache_dir' => __DIR__.'/cache/'))
$facade = PHPPdf\Core\FacadeBuilder::create()
// set cache
//                                               ->setCache('File', array('cache_dir' => __DIR__.'/cache/'))
//                                               ->setUseCacheForStylesheetConstraint(false)
//                                               ->setUseCacheForStylesheetConstraint(true)
                                               ->build();

if($_SERVER['argc'] < 3) 
{
    echo 'Pass example name and destination file path, for example `cli.php example-name /some/destination/file.pdf`'.PHP_EOL;
    echo 'Available examples:'.PHP_EOL;
    $examples = get_examples();
    die(implode(PHP_EOL, $examples));
}

$name = basename($_SERVER['argv'][1]);
$destinationPath = $_SERVER['argv'][2];

$documentFilename = __DIR__.'/'.$name.'.xml';
$stylesheetFilename = __DIR__.'/'.$name.'-style.xml';

if(!is_readable($documentFilename) || !is_readable($stylesheetFilename))
{
    die(sprintf('Example "%s" dosn\'t exist.', $name));
}

if(!is_writable(dirname($destinationPath)))
{
    die(sprintf('"%s" isn\'t writable.', $destinationPath));
}

$xml = str_replace('dir:', __DIR__.'/', file_get_contents($documentFilename));
$stylesheetXml = str_replace('dir:', __DIR__.'/', file_get_contents($stylesheetFilename));
$stylesheet = PHPPdf\DataSource\DataSource::fromString($stylesheetXml);

$start = microtime(true);

$content = $facade->render($xml, $stylesheet);

echo 'time: '.(microtime(true) - $start).'s';

file_put_contents($destinationPath, $content);
