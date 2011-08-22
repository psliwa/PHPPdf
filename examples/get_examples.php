<?php

function get_examples()
{
    $examples = array();
    
    $iter = new GlobIterator(__DIR__.'/*.xml');
    foreach($iter as $file)
    {
        $name = $file->getBasename('.xml');
        if(strpos($name, '-style') === false)
        {
            $examples[] = $name;
        }
    }
    
    return $examples;
}