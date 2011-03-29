<?php

abstract class TestCase extends PHPUnit_Framework_TestCase
{
    public function  __construct($name = NULL, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->init();
    }

    protected function init()
    {
    }

    public function invokeMethod($object, $methodName, array $args = array())
    {
        $refObject = new ReflectionObject($object);
        $method = $refObject->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }
    
    public static function assertInstanceOf($expectedClass, $actual, $message = '')
    {
    	self::assertTrue($actual instanceof $expectedClass, $message);
    }
}