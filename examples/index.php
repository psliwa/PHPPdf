<?php

error_reporting(E_ALL | E_NOTICE);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

set_time_limit(0);

require_once __DIR__.'/get_examples.php';
require_once __DIR__.'/../vendor/autoload.php';

if(!isset($_GET['name']))
{
    echo 'Available examples:<br />';
    $examples = get_examples();
    echo '<ul>';
    foreach($examples as $example)
    {
        echo '<li>'.$example.' (<a href="?name='.$example.'">pdf</a> or <a href="?name='.$example.'&engine=image">image</a>)';
    }
    echo '</ul>';
    exit();
}

$engine = isset($_GET['engine']) ? $_GET['engine'] : 'pdf';

// set different way of configuration
//$facade = PHPPdf\Core\FacadeBuilder::create(new PHPPdf\Core\Configuration\DependencyInjection\LoaderImpl())//->setCache('File', array('cache_dir' => __DIR__.'/cache/'))
$facade = PHPPdf\Core\FacadeBuilder::create()
// set cache
//                                               ->setCache('File', array('cache_dir' => __DIR__.'/cache/'))
//                                               ->setUseCacheForStylesheetConstraint(false)
//                                               ->setUseCacheForStylesheetConstraint(true)
//->setDocumentParserType(PHPPdf\Parser\FacadeBuilder::PARSER_MARKDOWN)
                                               ->setEngineType($engine)
                                               ->setEngineOptions(array(
                                                   'format' => 'jpg',
                                                   'quality' => 70,
                                                   'engine' => 'imagick',
                                               ))
                                               ->build();

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
    if($engine == 'pdf')
    {
        header('Content-Type: application/pdf');
        echo $content;
    }
    else
    {
        foreach($content as $data)
        {
            $data = base64_encode($data);
        
            echo '<img src="data:image/jpeg;base64,'.$data.'" />';
        }
    }
}
