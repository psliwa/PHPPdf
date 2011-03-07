<?php

namespace PHPPdf\Util;

/**
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