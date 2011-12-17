<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Parser;

use PHPPdf\Parser\Exception\ParseException;
use PHPPdf\Parser\XmlParser;

/**
 * Document to generate
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ColorPaletteParser extends XmlParser
{
    const ROOT_TAG = 'colors';
    
    protected function createRoot()
    {
        return array();
    }

    protected function parseElement(\XMLReader $reader)
    {
        if($reader->name === 'color')
        {
            $name = $reader->getAttribute('name');
            $hex = $reader->getAttribute('hex');
            
            if(!$name || !$hex)
            {
                throw new ParseException('"name" and "hex" attributes are required for "color" tag.');
            }
            
            $root = &$this->getFirstElementFromStack();
            $root[$name] = $hex;
        }
    }
    
    public function parseEndElement(\XMLReader $reader)
    {
    }
}