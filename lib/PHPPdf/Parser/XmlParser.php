<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Parser;

use PHPPdf\Parser\Exception\ParseException;

use PHPPdf\Parser\Exception as Exceptions;

/**
 * Base class for xml parsers. This class uses XMLReader library from php's core.
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
abstract class XmlParser implements Parser
{
    private $stack = array();
    private $stackSize = 0;
    private $xmlReaderProvidedFromOutside = false;

    public function parse($content)
    {
        $root = $this->createRoot();
        $this->pushOnStack($root);

        $reader = $this->getReader($content);

        $stopParsing = false;
        do
        {
            switch($reader->nodeType)
            {
                case \XMLReader::ELEMENT:
                    $this->parseElement($reader);
                    break;
                case \XMLReader::END_ELEMENT:
                    $this->parseEndElement($reader);
                    if($this->isEndOfParsedDocument($reader))
                    {
                        $stopParsing = true;
                    }

                    break;
                case \XMLReader::TEXT:
                case \XMLReader::SIGNIFICANT_WHITESPACE:
                case \XMLReader::WHITESPACE:                    
                case \XMLReader::CDATA:
                case \XMLReader::ENTITY:
                case \XMLReader::ENTITY_REF:
                    $this->parseText($reader);
                    break;
            }
        }
        while(!$stopParsing && $this->read($reader));

        $this->stack = array();
        $this->stackSize = 0;
        
        if(!$this->xmlReaderProvidedFromOutside)
        {
            $reader->close();            
        }
        
        $this->reset();

        return $root;
    }
    
    protected function reset()
    {
    }

    private function getReader($content)
    {
        if($content instanceof \XMLReader)
        {
            $reader = $content;
            $this->xmlReaderProvidedFromOutside = true;
        }
        else
        {
            $reader = $this->createReader($content);
 
            $nodeType = $reader->nodeType;
            while($reader->nodeType !== \XMLReader::ELEMENT)
            {
                $this->read($reader);
            }

            if($reader->name != static::ROOT_TAG)
            {
                throw new Exceptions\InvalidTagException(sprintf('Root of xml document must be "%s", "%s" given.', static::ROOT_TAG, $reader->name));
            }
            
            $this->parseRootAttributes($reader);
            
            $this->seekReaderToNextTag($reader);
        }

        return $reader;
    }
    
    /**
     * Converts XMLReader's error on ParseException
     */
    protected function read(\XMLReader $reader)
    {
        libxml_clear_errors();
        
        $status = @$reader->read();

        $error = libxml_get_last_error();
        if($error)
        {
            libxml_clear_errors();
            throw new Exceptions\ParseException(sprintf('Xml parsing error "%s" in file "%s" on line %s on column %s', $error->message, $error->file, $error->line, $error->column));
        }

        return $status;
    }

    protected function createReader($content)
    {
        $reader = new \XMLReader();
        $content = ltrim($content);

        if($this->isXmlDocument($content))
        {
            $reader->XML($content, null, LIBXML_NOBLANKS | LIBXML_DTDLOAD);
        }
        else
        {
            $success = @$reader->open($content, null, LIBXML_NOBLANKS | LIBXML_DTDLOAD);
            
            if(!$success)
            {
                throw new ParseException(sprintf('File "%s" doesn\'t exist or is unreadable', $content));
            }
        }

        $reader->setParserProperty(\XMLReader::SUBST_ENTITIES, true);

        return $reader;
    }
    
    private function isXmlDocument($content)
    {
        return strpos($content, '<') === 0;
    }

    protected function seekReaderToNextTag(\XMLReader $reader)
    {
        $result = null;
        do
        {
            $result = $this->read($reader);
        }
        while($result && $reader->nodeType !== \XMLReader::ELEMENT && $reader->nodeType !== \XMLReader::TEXT);
    }
    
    protected function parseRootAttributes(\XMLReader $reader)
    {
    }

    abstract protected function createRoot();

    abstract protected function parseElement(\XMLReader $reader);

    abstract protected function parseEndElement(\XMLReader $reader);

    protected function parseText(\XMLReader $reader)
    {
    }

    protected function &getLastElementFromStack()
    {
        return $this->stack[$this->stackSize-1];
    }
    
    protected function &getFirstElementFromStack()
    {
        return $this->stack[0];
    }

    protected function pushOnStack(&$element)
    {
        $this->stack[] = &$element;
        $this->stackSize++;
    }

    protected function popFromStack()
    {
        $this->stackSize--;
        return array_pop($this->stack);
    }

    /**
     * @return boolean True if parser reach to the end of the document, otherwise false
     */
    protected function isEndOfParsedDocument(\XMLReader $reader)
    {
        return $reader->name == static::ROOT_TAG;
    }
    
    protected function clearStack()
    {
        $this->stack = array();
        $this->stackSize = 0;
    }
}