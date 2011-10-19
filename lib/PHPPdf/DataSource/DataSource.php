<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\DataSource;

/**
 * Data source class
 * 
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

    /**
     * Read data from source
     * 
     * @return string Data from source
     */
    abstract public function read();

    /**
     * @return string Identifier of data source
     */
    abstract public function getId();
}