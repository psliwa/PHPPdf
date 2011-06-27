<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Parser;

use PHPPdf\Parser\Exception as Exceptions;

/**
 * Base class for xml parsers. This class uses XMLReader library from php's core.
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
abstract class XmlParser implements Parser
{
    private $stack = array();
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
                case \XMLReader::ENTITY:
                case \XMLReader::ENTITY_REF:
                    $this->parseText($reader);
                    break;
            }
        }
        while(!$stopParsing && $reader->read());

        $this->stack = array();
        
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
            $reader = new \XMLReader();
            

            $reader->XML($content, null, LIBXML_NOBLANKS | LIBXML_DTDLOAD);
            $reader->setParserProperty(\XMLReader::SUBST_ENTITIES, true);
 
            while($reader->nodeType !== \XMLReader::ELEMENT)
            {
                $reader->read();
            }

            if($reader->name != static::ROOT_TAG)
            {
                throw new Exceptions\InvalidTagException(sprintf('Root of xml document must be "%s", "%s" given.', static::ROOT_TAG, $reader->name));
            }
            $this->seekReaderToNextTag($reader);
        }

        return $reader;
    }

    protected function seekReaderToNextTag(\XMLReader $reader)
    {
        $result = null;
        do
        {
            $result = $reader->read();
        }
        while($result && $reader->nodeType !== \XMLReader::ELEMENT);
    }

    abstract protected function createRoot();

    abstract protected function parseElement(\XMLReader $reader);

    abstract protected function parseEndElement(\XMLReader $reader);

    protected function parseText(\XMLReader $reader)
    {
    }

    protected function &getLastElementFromStack()
    {
        return $this->stack[count($this->stack)-1];
    }
    
    protected function &getFirstElementFromStack()
    {
        return $this->stack[0];
    }

    protected function pushOnStack(&$element)
    {
        $this->stack[] = &$element;
    }

    protected function popFromStack()
    {
        return array_pop($this->stack);
    }

    /**
     * @return boolean True if parser reach to the end of the document, otherwise false
     */
    protected function isEndOfParsedDocument(\XMLReader $reader)
    {
        return $reader->name == static::ROOT_TAG;
    }
}