<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core;

use PHPPdf\Exception\InvalidArgumentException;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class FontRegistry implements \Countable
{
    private $fonts = array();
    private $document = null;
    
    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    public function register($name, array $font)
    {
        $font = $this->document->createFont($font);

        $this->add($name, $font);
    }

    private function add($name, $font)
    {
        $name = (string) $name;
        $this->fonts[$name] = $font;
    }

    public function get($name)
    {
        if($this->has($name))
        {
            return $this->fonts[$name];
        }

        throw new InvalidArgumentException(sprintf('Font "%s" is not registered.', $name));
    }

    public function has($name)
    {
        return isset($this->fonts[$name]);
    }

    public function count()
    {
        return count($this->fonts);
    }
}