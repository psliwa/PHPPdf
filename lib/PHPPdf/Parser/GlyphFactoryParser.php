<?php

namespace PHPPdf\Parser;

use PHPPdf\Glyph\Factory as GlyphFactory,
    PHPPdf\Parser\Exception\ParseException;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class GlyphFactoryParser extends XmlParser
{
    const ROOT_TAG = 'glyphs';
    const GLYPH_TAG = 'glyph';
    const STYLESHEET_TAG = 'stylesheet';
    const FORMATTERS_TAG = 'formatters';
    const FORMATTER_TAG = 'formatter';
    const INVOKE_TAG = 'invoke';

    private $stylesheetParser;
    private $isFormattersParsing = false;
    
    private $lastTag = null;

    public function  __construct()
    {
        $this->stylesheetParser = new StylesheetParser();
    }
    
    protected function reset()
    {
        $this->lastTag = null;
    }

    public function getStylesheetParser()
    {
        return $this->stylesheetParser;
    }

    public function setStylesheetParser(StylesheetParser $stylesheetParser)
    {
        $this->stylesheetParser = $stylesheetParser;
    }

    protected function createRoot()
    {
        return new GlyphFactory();
    }

    protected function parseElement(\XMLReader $reader)
    {
        if($reader->name === self::GLYPH_TAG)
        {
            $this->parseGlyph($reader);
        }
        elseif($reader->name === self::STYLESHEET_TAG)
        {
            $this->parseStylesheet($reader);
        }
        elseif($reader->name === self::FORMATTERS_TAG)
        {
            $this->isFormattersParsing = true;
        }
        elseif($reader->name === self::FORMATTER_TAG)
        {
            $this->parseFormatter($reader);
        }
        elseif($reader->name === self::INVOKE_TAG)
        {
            $this->parseInvoke($reader);
        }
    }

    private function parseGlyph(\XMLReader $reader)
    {
        $root = $this->getLastElementFromStack();

        $name = trim($reader->getAttribute('name'));
        $class = trim($reader->getAttribute('class'));

        if(!$name || !$class)
        {
            throw new ParseException('"name" and "class" attribute are required.');
        }

        $glyph = new $class();
        $root->addPrototype($name, $glyph);

        $this->pushOnStack($glyph);
        
        $this->lastTag = $name;
    }

    private function parseStylesheet(\XMLReader $reader)
    {
        $this->seekReaderToNextTag($reader);
        $bagContainer = $this->getStylesheetParser()->parse($reader);

        $glyph = $this->getLastElementFromStack();

        $attributeBag = $bagContainer->getAttributeBag();
        $enhancementBag = $bagContainer->getEnhancementBag();

        foreach($attributeBag->getAll() as $name => $value)
        {
            $glyph->setAttribute($name, $value);
        }

        foreach($enhancementBag->getAll() as $name => $parameters)
        {
            $glyph->mergeEnhancementAttributes($name, $parameters);
        }
    }

    private function parseFormatter(\XMLReader $reader)
    {
        $glyph = $this->getLastElementFromStack();

        $formatterClassName = $reader->getAttribute('class');

        $glyph->addFormatterName($formatterClassName);
    }
    
    private function parseInvoke(\XMLReader $reader)
    {
        $method = $reader->getAttribute('method');
        $argId = $reader->getAttribute('argId');
        
        $factory = $this->getFirstElementFromStack();
        $factory->addInvocationsMethodsOnCreate($this->lastTag, $method, $argId);
    }

    protected function parseEndElement(\XMLReader $reader)
    {
        if($reader->name === self::FORMATTERS_TAG)
        {
            $this->isFormattersParsing = false;
        }
        elseif(!$this->isFormattersParsing)
        {
            $this->popFromStack();
        }
    }
}