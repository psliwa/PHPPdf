<?php

namespace PHPPdf\Parser;

use PHPPdf\Font\Font,
    PHPPdf\Parser\Exception\ParseException;

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

        $const = sprintf('PHPPdf\Font\Font::STYLE_%s', str_replace('-', '_', strtoupper($name)));
        $style = \constant($const);

        $this->currentFontStyles[$style] = $this->createFont($reader, $name, $style);
    }

    private function createFont(\XMLReader $reader, $name, $style)
    {
        $file = $reader->getAttribute('file');
        $type = $reader->getAttribute('type');

        if($file)
        {
            $file = str_replace('%resources%', __DIR__.'/../Resources', $reader->getAttribute('file'));
            $font = \Zend_Pdf_Font::fontWithPath($file);
        }
        elseif($type)
        {
            $const = sprintf('\Zend_Pdf_Font::FONT_%s', str_replace('-', '_', strtoupper($type)));

            if(!defined($const))
            {
                throw new ParseException(sprintf('Unrecognized font type: "%s" for font "%s: %s".', $type, $name, $style));
            }

            $fontName = constant($const);
            $font = \Zend_Pdf_Font::fontWithName($fontName);
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
            try
            {
                $root = &$this->getLastElementFromStack();
                $root[$this->currentFontName] = new Font($this->currentFontStyles);
                $this->currentFontName = null;
                $this->currentFontStyles = array();
            }
            catch(\InvalidArgumentException $e)
            {
                throw new ParseException('Invalid font types or filepaths', 1, $e);
            }
        }
    }
}