<?php

namespace PHPPdf\Util;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
abstract class DataSource
{
    public static function fromFile($filePath)
    {
        return new FileDataSource($filePath);
    }

    public static function fromString($content)
    {
        return new StringDataSource($content);
    }

    abstract public function read();

    abstract public function getId();
}