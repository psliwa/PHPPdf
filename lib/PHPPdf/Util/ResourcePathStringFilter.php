<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Util;

/**
 * Resource path string filter
 * 
 * Replaces %resources% string to path to Resources directory
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ResourcePathStringFilter implements StringFilter
{
    private $path;
    
    public function filter($value)
    {
        return str_replace('%resources%', $this->getPathToResources(), $value);;
    }
    
    private function getPathToResources()
    {
        if($this->path === null)
        {
            $this->path = str_replace('\\', '/', realpath(__DIR__.'/../Resources'));
        }
        
        return $this->path;
    }
}