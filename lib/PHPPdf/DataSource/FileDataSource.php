<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\DataSource;

use PHPPdf\Exception\InvalidArgumentException;

/**
 * File data source class
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class FileDataSource extends DataSource
{
    private $filePath;

    public function __construct($filePath)
    {
        if(!is_readable($filePath))
        {
            throw new InvalidArgumentException(sprintf('File "%s" dosn\'t exist or is unreadable.', $filePath));
        }

        $this->filePath = (string) $filePath;
    }

    public function read()
    {
        return file_get_contents($this->filePath);
    }

    public function getId()
    {
        return str_replace('-', '_', crc32($this->filePath));
    }
}