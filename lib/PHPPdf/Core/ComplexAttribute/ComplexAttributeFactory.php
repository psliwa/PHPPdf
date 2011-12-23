<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\ComplexAttribute;

use PHPPdf\Exception\InvalidArgumentException;

use PHPPdf\Core\ComplexAttribute\Exception\DefinitionNotFoundException;

/**
 * Factory of ComplexAttribute objects.
 *
 * Factory may by populated by (@see addDefinition()) method. Factory determines
 * complexAttribute parameters (also determines if parameter is required or not)
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ComplexAttributeFactory implements \Serializable
{
    private $definitions = array();
    private $constructors = array();
    private $classes = array();
    private $constructorParameters = array();
    private $instances = array();
    
    public function __construct(array $definitions = array())
    {
        foreach($definitions as $name => $className)
        {
            $this->addDefinition($name, $className);
        }
    }

    public function addDefinition($name, $className)
    {
        $this->definitions[$name] = $className;
    }

    public function hasDefinition($name)
    {
        return isset($this->definitions[$name]);
    }

    public function getParameters($name)
    {
        $parameters = $this->getConstructorParameters($name);

        $parametersNames = array();

        foreach($parameters as $parameter)
        {
            $parametersNames[] = $parameter->getName();
        }

        return $parametersNames;
    }

    /**
     * @return \ReflectionMethod
     */
    private function getConstructor($name)
    {
        if(!isset($this->constructors[$name]))
        {
            $className = $this->getDefinition($name);
            $this->constructors[$name] = new \ReflectionMethod($className, '__construct');
        }

        return $this->constructors[$name];
    }

    private function getDefinition($name)
    {
        if(!isset($this->definitions[$name]))
        {
            throw new DefinitionNotFoundException(sprintf('Definition of "%s" not found.', $name));
        }

        return $this->definitions[$name];
    }

    /**
     * Only for unit testing
     *
     * @internal
     * @return array
     */
    private function getDefinitions()
    {
        return $this->definitions;
    }
    
    public function getDefinitionNames()
    {
        return array_keys($this->definitions);
    }

    private function getConstructorParameters($name)
    {
        if(!isset($this->constructorParameters[$name]))
        {
            $constructor = $this->getConstructor($name);
            $this->constructorParameters[$name] = $constructor->getParameters();
        }

        return $this->constructorParameters[$name];
    }

    /**
     * Return instance of ComplexAttribute registered under passed named and parameters.
     * 
     * Internally this method uses Flyweight pattern to reuse complexAttribute's objects
     *
     * @param string $name Name of complexAttribute
     * @param array $parameters Parameters of complexAttribute
     * 
     * @return PHPPdf\Core\ComplexAttribute\ComplexAttribute
     */
    public function create($name, array $parameters = array())
    {
        $key = $this->getInstanceKey($name, $parameters);
        
        if(!isset($this->instances[$key]))
        {
            $this->instances[$key] = $this->createInstance($name, $parameters);
        }
        
        return $this->instances[$key];
    }
    
    private function getInstanceKey($name, array $parameters)
    {
        return md5($name.serialize($parameters));
    }
    
    private function createInstance($name, array $parameters)
    {
        $args = array();
        $constructor = $this->getConstructor($name);

        $constructorParameters = $this->getConstructorParameters($name);

        foreach($constructorParameters as $parameter)
        {
            $value = $this->getParameterValue($parameter, $parameters, $name);

            $args[$parameter->getName()] = $value;
        }
        
        if($parameters)
        {
            throw new InvalidArgumentException(sprintf('Unexpected parameters (%s) passed to "%s" complex attribute.', implode(', ', array_keys($parameters)), $name));
        }        
        
        $class = $this->getClass($name);
        return $class->newInstanceArgs($args);
    }
    
    private function getParameterValue(\ReflectionParameter $parameter, array &$values, $complexAttributeName)
    {
        $acceptableNames = $this->getAcceptableParameterNames($parameter);
        if(!$this->existsAtLeastOneKey($acceptableNames, $values) && !$parameter->isOptional())
        {
            throw new InvalidArgumentException(sprintf('Parameter "%s" is required for "%s" complexAttribute.', $parameter->getName(), $complexAttributeName));
        }

        foreach($acceptableNames as $name)
        {
            if(isset($values[$name]))
            {
                $value = $values[$name];
                unset($values[$name]);
                break;
            }
        }
        
        if(!isset($value))
        {
            $value = $parameter->getDefaultValue();
        }

        return $value;
    }
    
    private function getAcceptableParameterNames(\ReflectionParameter $parameter)
    {
        $names[] = $parameter->getName();
        
        $uncamelizedName = $this->uncamelizeParameterName($parameter->getName());
        
        if($uncamelizedName != $parameter->getName())
        {
            $names[] = $uncamelizedName;
        }
        
        return $names;
    }
    
    private function uncamelizeParameterName($name)
    {
        $name = ucfirst($name);
        $matches = array();
        preg_match_all('([A-Z]{1}[a-z0-9]*)', $name, $matches);
               
        $parts = $matches[0];
        foreach($parts as $key => $part)
        {
            $parts[$key] = lcfirst($part);
        }
        
        return implode('-', $parts);
    }
    
    private function existsAtLeastOneKey(array $keys, array $array)
    {
        foreach($keys as $key)
        {
            if(isset($array[$key]))
            {
                return true;
            }
        }
        
        return false;
    }

    /**
     * @return \ReflectionClass
     */
    private function getClass($name)
    {
        if(!isset($this->classes[$name]))
        {
            $className = $this->getDefinition($name);

            $this->classes[$name] = new \ReflectionClass($className);
        }

        return $this->classes[$name];
    }

    public function serialize()
    {
        return serialize($this->definitions);
    }

    public function unserialize($serialized)
    {
        $definitions = \unserialize($serialized);

        foreach($definitions as $name => $className)
        {
            $this->addDefinition($name, $className);
        }
    }
}