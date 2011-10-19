<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Parser;

use PHPPdf\Parser\XmlParser;
use PHPPdf\Core\ComplexAttribute\ComplexAttributeFactory;
use PHPPdf\Core\Parser\StylesheetConstraint;
use PHPPdf\Parser\Exception\ParseException;

/**
 * Xml stylesheet parser
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class StylesheetParser extends XmlParser
{
    const ROOT_TAG = 'stylesheet';
    const ATTRIBUTE_TAG = 'attribute';
    const ENHANCEMENT_TAG = 'enhancement';
    const COMPLEX_ATTRIBUTE_TAG = 'complex-attribute';
    const ANY_TAG = 'any';
    const ATTRIBUTE_CLASS = 'class';
    const STYLE_ATTRIBUTE = 'style';
    
    private $throwsExceptionOnConstraintTag = false;
    private $root;
    private $complexAttributeFactory;

    public function __construct(StylesheetConstraint $root = null, $throwExceptionOnConstraintTag = false)
    {
        $this->setRoot($root);
        $this->setThrowsExceptionOnConstraintTag($throwExceptionOnConstraintTag);
    }

    public function setRoot(StylesheetConstraint $root = null)
    {
        $this->root = $root;
    }
    
    public function setThrowsExceptionOnConstraintTag($flag)
    {
        $this->throwsExceptionOnConstraintTag = (boolean) $flag;
    }

    public function setComplexAttributeFactory(ComplexAttributeFactory $complexAttributeFactory)
    {
        $this->complexAttributeFactory = $complexAttributeFactory;
    }
    
    /**
     * @return StylesheetConstraint
     */
    protected function createRoot()
    {
        return ($this->root ? $this->root : new StylesheetConstraint());
    }
    
    public function parse($content)
    {
        $stylesheetConstraint = parent::parse($content);

        $this->root = null;
        $this->clearStack();

        return $stylesheetConstraint;
    }

    protected function parseElement(\XMLReader $reader)
    {
        $tag = $reader->name;

        if($tag === self::ATTRIBUTE_TAG)
        {
            $this->parseAttribute($reader);
        }
        elseif($tag === self::ENHANCEMENT_TAG || $tag === self::COMPLEX_ATTRIBUTE_TAG)
        {
            $this->parseComplexAttribute($reader);
        }
        else
        {
            $this->parseConstraint($reader, $tag);
        }
    }

    private function parseAttribute(\XMLReader $reader)
    {
        $lastConstraint = $this->getLastElementFromStack();

        $name = $reader->getAttribute('name');

        if(!$name)
        {
            throw new ParseException('Name of attribute is required.');
        }

        $value = $reader->getAttribute('value');

        if($value === null)
        {
            throw new ParseException('Value of attribute is required.');
        }

        $lastConstraint->add($name, $value);
    }

    private function parseComplexAttribute(\XMLReader $reader)
    {
        $lastConstraint = $this->getLastElementFromStack();

        $attributes = array();

        while($reader->moveToNextAttribute())
        {
            $attributes[$reader->name] = $reader->value;
        }

        if(!isset($attributes['name']))
        {
            throw new ParseException('Name of complex attribute is required.');
        }

        $id = $attributes['name'];
        
        if(isset($attributes['id']))
        {
            $id = $attributes['id'];
            unset($attributes['id']);
        }

        $lastConstraint->add($id, $attributes);
    }

    private function parseConstraint(\XMLReader $reader, $tag)
    {
        if($this->throwsExceptionOnConstraintTag)
        {
            throw new ParseException(sprintf('Unknown tag "%s" in stylesheet section.', $tag));
        }
        
        $isEmptyElement = $reader->isEmptyElement;
        
        $lastConstraint = $this->getLastElementFromStack();

        $constraint = new StylesheetConstraint();

        $class = $reader->getAttribute(self::ATTRIBUTE_CLASS);
        $classes = preg_split('/\s+/', $class);

        if(!$tag)
        {
            throw new ParseException('You must set tag name.');
        }

        $lastConstraint->addConstraint($tag, $constraint);
        foreach($classes as $class)
        {
            if($class)
            {
                $constraint->addClass($class);
            }
        }
        
        $this->addConstraintsFromAttributes($constraint, $reader);
        
        $this->pushOnStack($constraint);
        
        if($isEmptyElement)
        {
            $this->parseEndElement($reader);
        }
    }
    
    public function addConstraintsFromAttributes(BagContainer $constraint, \XMLReader $reader, array $ignoredAttributes = array(self::ATTRIBUTE_CLASS))
    {
        while($reader->moveToNextAttribute())
        {
            $attributes = $this->getAttributesFromXmlAttribute($reader);
            
            foreach($attributes as $name => $value)
            {
                if(!in_array($name, $ignoredAttributes))
                {
                    if($complexAttributeName = $this->getComplexAttributeName($name))
                    {
                        $propertyName = substr($name, strlen($complexAttributeName) + 1);
                        $constraint->add($complexAttributeName, array('name' => $complexAttributeName, $propertyName => $value));
                    }
                    else
                    {
                        $constraint->add($name, $value);
                    }
                }
            }
        }
    }
    
    private function getAttributesFromXmlAttribute(\XMLReader $reader)
    {
        $name = $reader->name;
        
        if($name === self::STYLE_ATTRIBUTE)
        {
            $attributes = array();
            
            if(@preg_match_all('/([a-zA-Z0-9\-\.]+)\s*:\s*(.+)\s*;/U', $reader->value, $matches, \PREG_SET_ORDER))
            {
                foreach($matches as $match)
                {
                    $attributes[trim($match[1])] = trim($match[2]);
                }
            }

            return $attributes;
        }
        else
        {
            return array($name => $reader->value);
        }
        
    }
    
    private function getComplexAttributeName($name)
    {
        if(!$this->complexAttributeFactory)
        {
            return false;
        }
        
        $complexAttributesNames = (array) $this->complexAttributeFactory->getDefinitionNames();
        
        foreach($complexAttributesNames as $complexAttributeName)
        {
            if(strpos($name, $complexAttributeName) === 0 && in_array(substr($name, strlen($complexAttributeName), 1), array('.', '-')))
            {
                return $complexAttributeName;
            }
        }
        
        return false;
    }

    protected function parseEndElement(\XMLReader $reader)
    {
        $tag = $reader->name;

        if($tag != self::ATTRIBUTE_TAG)
        {
            $this->popFromStack();
        }
    }
}