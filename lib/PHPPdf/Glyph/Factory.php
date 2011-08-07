<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph;

/**
 * Factory of the glyphs based on Factory Method and Prototype design pattern
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
use PHPPdf\Exception\UnregisteredGlyphException;

class Factory implements \Serializable
{
    private $prototypes = array();
    private $invocationsMethodsOnCreate = array();
    private $invokeArgs = array();

    public function addPrototype($name, Glyph $glyph, array $invocationsMethodsOnCreate = array())
    {
        $name = (string) $name;

        $this->prototypes[$name] = $glyph;
        $this->invocationsMethodsOnCreate[$name] = $invocationsMethodsOnCreate;
    }
    
    public function addPrototypes(array $prototypes)
    {
        foreach($prototypes as $name => $glyph)
        {
            $this->addPrototype($name, $glyph);
        }
    }
    
    /**
     * Adds method and argument tag to invoke after creating
     * 
     * @see create()
     * 
     * @param string $name Name of prototype
     * @param string $invocationMethodName Name of setter method
     * @param string $invocationMethodArgId Argument id, {@see addInvokeArg()}
     */
    public function addInvocationsMethodsOnCreate($name, $invocationMethodName, $invocationMethodArgId)
    {
        $this->invocationsMethodsOnCreate[$name][$invocationMethodName] = $invocationMethodArgId;
    }
    
    /**
     * Adds argument witch can be used as argument of setter method on factory products
     * 
     * @param string $tag Tag of argument
     * @param mixed $value Value of argument
     */
    public function addInvokeArg($tag, $value)
    {
        $this->invokeArgs[$tag] = $value;
    }
    
    public function getInvokeArgs()
    {
        return $this->invokeArgs;
    }
    
    public function invocationsMethodsOnCreate()
    {
        return $this->invocationsMethodsOnCreate;
    }

    /**
     * Create copy of glyph stored under passed name
     *
     * @param string Name/key of prototype
     * @return PHPPdf\Glyph\Glyph Deep copy of glyph stored under passed name
     * @throws PHPPdf\Exception\UnregisteredGlyphException If prototype with passed name dosn't exist
     */
    public function create($name)
    {
        $prototype = $this->getPrototype($name);

        $product = $prototype->copy();
        
        foreach($this->invocationsMethodsOnCreate[$name] as $methodName => $argTag)
        {
            if(isset($this->invokeArgs[$argTag]))
            {
                $arg = $this->invokeArgs[$argTag];
                $product->$methodName($arg);
            }
        }
        
        return $product;
    }

    /**
     * @return PHPPdf\Glyph\Glyph
     * @throws PHPPdf\Exception\UnregisteredGlyphException If prototype with passed name dosn't exist
     */
    public function getPrototype($name)
    {
        $name = (string) $name;

        if(!$this->hasPrototype($name))
        {
            UnregisteredGlyphException::glyphNotRegisteredException($name);
        }

        return $this->prototypes[$name];
    }

    public function hasPrototype($name)
    {
        $name = (string) $name;

        return isset($this->prototypes[$name]);
    }

    public function serialize()
    {
        return serialize(array(
            'prototypes' => $this->prototypes,
            'invocationsMethodsOnCreate' => $this->invocationsMethodsOnCreate,
            'invokeArgs' => $this->invokeArgs,
        ));
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        
        $prototypes = $data['prototypes'];
        $invocationsMethodsOnCreate = $data['invocationsMethodsOnCreate'];
        $invokeArgs = $data['invokeArgs'];

        foreach($prototypes as $name => $prototype)
        {
            $invocationsMethods = isset($invocationsMethodsOnCreate[$name]) ? $invocationsMethodsOnCreate[$name] : array();
            $this->addPrototype($name, $prototype, $invocationsMethods);
        }   

        foreach($invokeArgs as $tag => $value)
        {
            $this->addInvokeArg($tag, $value);
        }
    }
}