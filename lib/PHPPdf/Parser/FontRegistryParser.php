<?php

namespace PHPPdf\Parser;

use PHPPdf\Font\Font,
    PHPPdf\Font\ResourceWrapper,
    PHPPdf\Font\Registry,
    PHPPdf\Parser\Exception\ParseException;

class FontRegistryParser extends XmlParser
{
    const ROOT_TAG = 'fonts';

    private $currentFontName = null;
    private $currentFontStyles = array();

    protected function createRoot()
    {
        return new Registry();
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
        try
        {
            $file = $reader->getAttribute('file');
            $type = $reader->getAttribute('type');

            if($file)
            {
                $file = str_replace('%resources%', __DIR__.'/../Resources', $reader->getAttribute('file'));
                $font = ResourceWrapper::fromFile($file);
            }
            elseif($type)
            {
                $font = ResourceWrapper::fromName($type);
            }
            else
            {
                throw new ParseException(sprintf('File or type attribute are required in font "%s: %s" definition.', $name, $style));
            }

            return $font;
        }
        catch(\InvalidArgumentException $e)
        {
            throw new ParseException(sprintf('Invalid attributes for "%s".', $name), 0, $e);
        }
    }

    protected function parseEndElement(\XMLReader $reader)
    {
        if($reader->name === 'font')
        {
            try
            {
                /* @var $registry Registry */
                $registry = $this->getLastElementFromStack();
                $registry->register($this->currentFontName, $this->currentFontStyles);
                
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