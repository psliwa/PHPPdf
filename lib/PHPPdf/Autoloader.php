<?php

namespace PHPPdf;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Autoloader
{
    public static function register($basePath = __DIR__)
    {
        \spl_autoload_register(function($name) use($basePath)
        {
            $path = \str_replace(array('\\', '_'), \DIRECTORY_SEPARATOR, $name).'.php';
            $path = $basePath.\DIRECTORY_SEPARATOR.$path;

            if(\is_readable($path))
            {
                return require $path;
            }

            return false;
        });
    }
}