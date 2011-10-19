<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\DataSource;

/**
 * String data source class
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class StringDataSource extends DataSource
{
    private $content;

    public function __construct($content)
    {
        $this->content = (string) $content;
    }

    public function read()
    {
        return $this->content;
    }

    public function getId()
    {
        return str_replace('-', '_', crc32($this->content));
    }
}