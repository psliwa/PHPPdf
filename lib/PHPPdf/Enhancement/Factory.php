<?php

namespace PHPPdf\Enhancement;

use PHPPdf\Enhancement\Exception\DefinitionNotFoundException;

/**
 * Factory of Enhancement objects.
 *
 * Factory may by populated by (@see addDefinition()) method. Factory determines
 * enhancement parameters (also determines if parameter is required or not)
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Factory
{
    private $definitions = array();
    private $constructors = array();
    private $classes = array();
    private $constructorParameters = array();

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
     * Return instance of Enhancement registered under passed named and parameters.
     *
     * @param string $name Name of enhancement
     * @param array $parameters Parameters of enhancement
     * @return PHPPdf\Enhancement\Enhancement
     */
    public function create($name, array $parameters = array())
    {
        $args = array();
        $constructor = $this->getConstructor($name);

        $constructorParameters = $this->getConstructorParameters($name);

        foreach($constructorParameters as $parameter)
        {
            $parameterName = $parameter->getName();
            if(!isset($parameters[$parameterName]) && !$parameter->isOptional())
            {
                throw new \InvalidArgumentException(sprintf('Parameter "%s" is required for "%s" enhancement.', $parameterName, $name));
            }

            $value = isset($parameters[$parameterName]) ? $parameters[$parameterName] : $parameter->getDefaultValue();

            $args[$parameterName] = $value;
        }
        $class = $this->getClass($name);
        return $class->newInstanceArgs($args);
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
}