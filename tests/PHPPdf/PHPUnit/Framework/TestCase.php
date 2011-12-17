<?php

namespace PHPPdf\PHPUnit\Framework;

use PHPPdf\Core\Document;

use PHPPdf\PHPUnit\Framework\Constraint\ValidateByCallback;
use PHPPdf\PHPUnit\Framework\MockObject\Stub\ComposeStub;

abstract class TestCase extends \PHPUnit_Framework_TestCase
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
        $refObject = new \ReflectionObject($object);
        $method = $refObject->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }
    
    protected static function returnCompose(array $stubs)
    {
        return new ComposeStub($stubs);
    }
    
    protected static function validateByCallback(\Closure $closure, TestCase $testCase)
    {
        return new ValidateByCallback($closure, $testCase);
    }
    
    public function writeAttribute($object, $attributeName, $value)
    {
        $class = new \ReflectionClass(get_class($object));
        $class->getParentClass();
        $attribute = $this->getProperty($class, $attributeName);
        $attribute->setAccessible(true);
        $attribute->setValue($object, $value);
    }
    
    private function getProperty(\ReflectionClass $class, $name)
    {
        while($class && !$class->hasProperty($name))
        {
            $class = $class->getParentClass();
        }
        
        if($class)
        {
            return $class->getProperty($name);
        }
        
        return null;
    }
    
    protected function createDocumentStub()
    {
        return new Document($this->getMock('PHPPdf\Core\Engine\Engine'));
    }
}