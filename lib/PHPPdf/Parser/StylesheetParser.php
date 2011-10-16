<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Parser;

use PHPPdf\Parser\Exception\ParseException;
use PHPPdf\Enhancement\Factory as EnhancementFactory;
use PHPPdf\Parser\StylesheetConstraint,
    PHPPdf\Parser\Exception as Exceptions;

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
    const ANY_TAG = 'any';
    const ATTRIBUTE_CLASS = 'class';
    
    private $throwsExceptionOnConstraintTag = false;
    private $root;
    private $enhancementFactory;

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

    public function setEnhancementFactory(EnhancementFactory $enhancementFactory)
    {
        $this->enhancementFactory = $enhancementFactory;
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
        elseif($tag === self::ENHANCEMENT_TAG)
        {
            $this->parseEnhancement($reader);
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
            throw new Exceptions\ParseException('Name of attribute is required.');
        }

        $value = $reader->getAttribute('value');

        if($value === null)
        {
            throw new Exceptions\ParseException('Value of attribute is required.');
        }

        $lastConstraint->add($name, $value);
    }

    private function parseEnhancement(\XMLReader $reader)
    {
        $lastConstraint = $this->getLastElementFromStack();

        $attributes = array();

        while($reader->moveToNextAttribute())
        {
            $attributes[$reader->name] = $reader->value;
        }

        if(!isset($attributes['name']))
        {
            throw new Exceptions\ParseException('Name of enhancement is required.');
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
            throw new Exceptions\ParseException('You must set tag name.');
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
            $name = $reader->name;
            
            if(!in_array($name, $ignoredAttributes))
            {
                if($complexAttributeName = $this->getComplexAttribute($name))
                {
                    $propertyName = substr($name, strlen($complexAttributeName) + 1);
                    $constraint->add($complexAttributeName, array('name' => $complexAttributeName, $propertyName => $reader->value));
                }
                else
                {
                    $constraint->add($name, $reader->value);
                }
            }
        }
    }
    
    private function getComplexAttribute($name)
    {
        if(!$this->enhancementFactory)
        {
            return false;
        }
        
        $complexAttributesNames = (array) $this->enhancementFactory->getDefinitionNames();
        
        foreach($complexAttributesNames as $complexAttributeName)
        {
            if(strpos($name, $complexAttributeName) === 0)
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