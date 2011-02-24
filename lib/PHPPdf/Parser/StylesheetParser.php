<?php

namespace PHPPdf\Parser;

use PHPPdf\Parser\StylesheetConstraint,
    PHPPdf\Parser\Exception as Exceptions;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class StylesheetParser extends XmlParser
{
    const ROOT_TAG = 'stylesheet';
    const ATTRIBUTE_TAG = 'attribute';
    const ENHANCEMENT_TAG = 'enhancement';
    const ANY_TAG = 'any';

    /**
     * @return StylesheetConstraint
     */
    protected function createRoot()
    {
        return new StylesheetConstraint();
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

        $lastConstraint->getAttributeBag()->add($name, $value);
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

        $lastConstraint->getEnhancementBag()->add($id, $attributes);
    }

    private function parseConstraint(\XMLReader $reader, $tag)
    {       
        $lastConstraint = $this->getLastElementFromStack();

        $constraint = new StylesheetConstraint();

        $class = $reader->getAttribute('class');
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
        
        $this->pushOnStack($constraint);
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