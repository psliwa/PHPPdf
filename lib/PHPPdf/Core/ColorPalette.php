<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */


namespace PHPPdf\Core;

/**
 * Color palette
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ColorPalette
{
    private $colors;
    
    public function __construct(array $colors = array())
    {
        $this->colors = $colors;
    }
    
    public function get($name)
    {
        $name = strtolower($name);
        return isset($this->colors[$name]) ? $this->colors[$name] : $name;
    }
    
    public function merge(array $colors)
    {
        $this->colors = $colors + $this->colors;
    }
    
    public function getAll()
    {
        return $this->colors;
    }
}