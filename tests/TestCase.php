<?php

abstract class TestCase extends PHPUnit_Framework_TestCase
{
    public function invokeMethod($object, $methodName, array $args = array())
    {
        $refObject = new ReflectionObject($object);
        $method = $refObject->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }
}