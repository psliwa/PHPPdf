<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Autoloader
{
    public static function register($basePath = null)
    {
        if($basePath === null)
        {
            $basePath = __DIR__.'/..';
        }
        
        $basePath .= DIRECTORY_SEPARATOR;
        
        \spl_autoload_register(function($name) use($basePath)
        {
            $path = sprintf('%s%s%s', $basePath, \str_replace('\\', \DIRECTORY_SEPARATOR, $name), '.php');

            if(\file_exists($path))
            {
                return require $path;
            }

            return false;
        });
    }
}