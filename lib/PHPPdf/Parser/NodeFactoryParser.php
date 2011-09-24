<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Parser;

use PHPPdf\Util\UnitConverter;

use PHPPdf\Node\Factory as NodeFactory,
    PHPPdf\Parser\Exception\ParseException;

/**
 * Parser for node config file
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class NodeFactoryParser extends XmlParser
{
    const ROOT_TAG = 'factory';
    const INVOKE_ARGS_TAG = 'invoke-args';
    const INVOKE_ARG_TAG = 'invoke-arg';
    const GLYPHS_TAG = 'nodes';
    const GLYPH_TAG = 'node';
    const STYLESHEET_TAG = 'stylesheet';
    const FORMATTERS_TAG = 'formatters';
    const FORMATTER_TAG = 'formatter';
    const INVOKE_TAG = 'invoke';

    private $stylesheetParser;
    private $isFormattersParsing = false;
    
    private $invokeArgsDefinitions = array();
    private $currentArg = null;
    
    private $lastTag = null;
    
    private $unitConverter = null;

    public function __construct()
    {
        $this->stylesheetParser = new StylesheetParser();
    }
    
    public function setUnitConverter(UnitConverter $converter)
    {
        $this->unitConverter = $converter;
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
        return new NodeFactory();
    }

    protected function parseElement(\XMLReader $reader)
    {
        if($this->isFormattersParsing)
        {
            $this->parseFormatter($reader->name, $reader);
        }
        elseif($reader->name === self::GLYPH_TAG)
        {
            $this->parseNode($reader);
        }
        elseif($reader->name === self::STYLESHEET_TAG)
        {
            $this->parseStylesheet($reader);
        }
        elseif($reader->name === self::FORMATTERS_TAG)
        {
            $this->isFormattersParsing = true;
        }
        elseif($reader->name === self::INVOKE_TAG)
        {
            $this->parseInvoke($reader);
        }
        elseif($reader->name === self::INVOKE_ARG_TAG)
        {
            $this->parseInvokeArg($reader);
        }
    }

    private function parseNode(\XMLReader $reader)
    {
        $root = $this->getLastElementFromStack();

        $name = trim($reader->getAttribute('name'));
        $class = trim($reader->getAttribute('class'));

        if(!$name || !$class)
        {
            throw new ParseException('"name" and "class" attribute are required.');
        }

        $node = new $class();
        if($this->unitConverter)
        {
            $node->setUnitConverter($this->unitConverter);
        }
        $root->addPrototype($name, $node);

        $this->pushOnStack($node);
        
        $this->lastTag = $name;
    }

    private function parseStylesheet(\XMLReader $reader)
    {
        $this->seekReaderToNextTag($reader);
        $bagContainer = $this->getStylesheetParser()->parse($reader);

        $node = $this->getLastElementFromStack();

        $attributeBag = $bagContainer->getAttributeBag();
        $enhancementBag = $bagContainer->getEnhancementBag();

        foreach($attributeBag->getAll() as $name => $value)
        {
            $node->setAttribute($name, $value);
        }

        foreach($enhancementBag->getAll() as $name => $parameters)
        {
            $node->mergeEnhancementAttributes($name, $parameters);
        }
    }

    private function parseFormatter($formatterType, \XMLReader $reader)
    {
        $node = $this->getLastElementFromStack();

        $formatterClassName = $reader->getAttribute('class');

        $node->addFormatterName($formatterType, $formatterClassName);
    }
    
    private function parseInvoke(\XMLReader $reader)
    {
        $method = $reader->getAttribute('method');
        $argId = $reader->getAttribute('argId');
        
        $factory = $this->getFirstElementFromStack();
        $factory->addInvocationsMethodsOnCreate($this->lastTag, $method, $argId);
    }
    
    private function parseInvokeArg(\XMLReader $reader)
    {
        $id = $reader->getAttribute('id');
        $value = $reader->getAttribute('value');
        $class = $reader->getAttribute('class');
        
        $factory = $this->getFirstElementFromStack();
        
        if($class)
        {
            $value = new $class();
        }
        
        $factory->addInvokeArg($id, $value);
    }

    protected function parseEndElement(\XMLReader $reader)
    {
        if($reader->name === self::FORMATTERS_TAG)
        {
            $this->isFormattersParsing = false;
        }
        elseif(!$this->isFormattersParsing && $reader->name === self::GLYPH_TAG)
        {
            $this->popFromStack();
        }
    }
}