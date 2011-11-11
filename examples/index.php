<?php

error_reporting(E_ALL | E_NOTICE);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

set_time_limit(240);

require_once __DIR__.'/get_examples.php';
require_once __DIR__.'/../lib/PHPPdf/Autoloader.php';

PHPPdf\Autoloader::register();
PHPPdf\Autoloader::register(dirname(__FILE__).'/../lib/vendor');

// set different way of configuration
//$facade = PHPPdf\Core\FacadeBuilder::create(new PHPPdf\Core\Configuration\DependencyInjection\LoaderImpl())//->setCache('File', array('cache_dir' => __DIR__.'/cache/'))
$facade = PHPPdf\Core\FacadeBuilder::create()
// set cache
//                                               ->setCache('File', array('cache_dir' => __DIR__.'/cache/'))
//                                               ->setUseCacheForStylesheetConstraint(false)
//                                               ->setUseCacheForStylesheetConstraint(true)
//->setDocumentParserType(PHPPdf\Parser\FacadeBuilder::PARSER_MARKDOWN)
                                               ->build();

if(!isset($_GET['name']))
{
    echo 'Pass example name by "name" parameter.<br />';
    
    echo 'Available examples:<br />';
    $examples = get_examples();
    foreach($examples as $example)
    {
        echo '<a href="?name='.$example.'">'.$example.'</a><br />';
    }
    exit();
}

$name = basename($_GET['name']);

$documentFilename = __DIR__.'/'.$name.'.xml';
$stylesheetFilename = __DIR__.'/'.$name.'-style.xml';

if(!is_readable($documentFilename))
{
    die(sprintf('Example "%s" dosn\'t exist.', $name));
}

$xml = str_replace('dir:', __DIR__.'/', file_get_contents($documentFilename));
$stylesheetXml =  is_readable($stylesheetFilename) ? str_replace('dir:', __DIR__.'/', file_get_contents($stylesheetFilename)) : null;
$stylesheet = $stylesheetXml ? PHPPdf\DataSource\DataSource::fromString($stylesheetXml) : null;

$start = microtime(true);

$content = $facade->render($xml, $stylesheet);


if(isset($_GET['t']))
{
    echo (microtime(true) - $start).'<br />';
    echo (memory_get_peak_usage(true)/1024/1024).'MB';    
}
else
{
    header('Content-Type: application/pdf');
    echo $content;
}
