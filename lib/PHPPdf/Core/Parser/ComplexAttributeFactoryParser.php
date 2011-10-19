<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Parser;

use PHPPdf\Parser\Exception\ParseException;
use PHPPdf\Parser\XmlParser;
use PHPPdf\Core\ComplexAttribute\ComplexAttributeFactory;

/**
 * ComplexAttribute factory parser
 * 
 * Parses config file for complexAttributes and creates ComplexAttributeFactory
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ComplexAttributeFactoryParser extends XmlParser
{
    const ROOT_TAG = 'complex-attributes';
    const COMPLEX_ATTRIBUTE_TAG = 'complex-attribute';

    protected function createRoot()
    {
        return new ComplexAttributeFactory();
    }

    protected function parseElement(\XMLReader $reader)
    {
        if($reader->name === self::COMPLEX_ATTRIBUTE_TAG)
        {
            $root = $this->getLastElementFromStack();

            $name = trim($reader->getAttribute('name'));
            $class = trim($reader->getAttribute('class'));

            if(!$name || !$class)
            {
                throw new ParseException('"name" and "class" attributes are required.');
            }

            $root->addDefinition($name, $class);
        }
    }

    protected function parseEndElement(\XMLReader $reader)
    {
    }
}