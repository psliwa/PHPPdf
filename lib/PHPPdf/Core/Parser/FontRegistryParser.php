<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Parser;

use PHPPdf\Parser\XmlParser;
use PHPPdf\Font\Registry;
use PHPPdf\Parser\Exception\ParseException;

/**
 * Parser for font config file
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class FontRegistryParser extends XmlParser
{
    const ROOT_TAG = 'fonts';

    private $currentFontName = null;
    private $currentFontStyles = array();

    protected function createRoot()
    {
        return array();
    }

    protected function parseElement(\XMLReader $reader)
    {
        if($reader->name === 'font')
        {
            $this->parseFont($reader);
        }
        else
        {
            $this->parseFontStyle($reader);
        }
    }

    private function parseFont(\XMLReader $reader)
    {
        $name = trim($reader->getAttribute('name'));

        if(!$name)
        {
            throw new ParseException('Font name is required.');
        }

        $this->currentFontName = $name;
    }

    private function parseFontStyle(\XMLReader $reader)
    {
        $name = $reader->name;

        $const = sprintf('PHPPdf\Core\Engine\Font::STYLE_%s', str_replace('-', '_', strtoupper($name)));
        $style = \constant($const);

        $this->currentFontStyles[$style] = $this->createFont($reader, $name, $style);
    }

    private function createFont(\XMLReader $reader, $name, $style)
    {
        $src = $reader->getAttribute('src');

        if($src)
        {
            $font = $src;
        }
        else
        {
            throw new ParseException(sprintf('File or type attribute are required in font "%s: %s" definition.', $name, $style));
        }

        return $font;
    }

    protected function parseEndElement(\XMLReader $reader)
    {
        if($reader->name === 'font')
        {
            $registry = &$this->getLastElementFromStack();
            $registry[$this->currentFontName] = $this->currentFontStyles;

            $this->currentFontName = null;
            $this->currentFontStyles = array();
        }
    }
}