<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Parser;

use PHPPdf\Enhancement\Factory as EnhancementFactory,
    PHPPdf\Parser\Exception\ParseException;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class EnhancementFactoryParser extends XmlParser
{
    const ROOT_TAG = 'enhancements';
    const ENHANCEMENT_TAG = 'enhancement';

    protected function createRoot()
    {
        return new EnhancementFactory();
    }

    protected function parseElement(\XMLReader $reader)
    {
        if($reader->name === self::ENHANCEMENT_TAG)
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