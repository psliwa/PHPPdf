<?php

namespace PHPPdf\Test\Core\ComplexAttribute;

use PHPPdf\Core\ComplexAttribute\ComplexAttributeFactory;
use PHPPdf\Stub\ComplexAttribute\ComplexAttributeStub;

class ComplexAttributeFactoryTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $factory;

    public function setUp()
    {
        $this->factory = new ComplexAttributeFactory();
    }

    /**
     * @test
     */
    public function setDefinitionOfComplexAttribute()
    {
        $this->assertFalse($this->factory->hasDefinition('stub'));
        $this->factory->addDefinition('stub', 'ComplexAttributeStub');
        $this->assertTrue($this->factory->hasDefinition('stub'));
    }

    /**
     * @test
     */
    public function getParameterNames()
    {
        $this->factory->addDefinition('stub', 'PHPPdf\Stub\ComplexAttribute\ComplexAttributeStub');
        $parameters = $this->factory->getParameters('stub');

        $this->assertEquals(array('color', 'someParameter'), $parameters);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function requiredParametersMustBePassed()
    {
        $this->factory->addDefinition('stub', 'PHPPdf\Stub\ComplexAttribute\ComplexAttributeStub');
        $this->factory->create('stub', array());
    }

    /**
     * @test
     * @dataProvider parameterNamesProvider
     */
    public function createUsingValidParameters($parameterName, $parameterValue, $propertyName)
    {
        $this->factory->addDefinition('stub', 'PHPPdf\Stub\ComplexAttribute\ComplexAttributeStub');
        $complexAttribute = $this->factory->create('stub', array('color' => '#cccccc', $parameterName => $parameterValue));

        $this->assertNotNull($complexAttribute);
        $this->assertInstanceOf('PHPPdf\Stub\ComplexAttribute\ComplexAttributeStub', $complexAttribute);
        $this->assertEquals($parameterValue, $this->readAttribute($complexAttribute, $propertyName));
    }
    
    public function parameterNamesProvider()
    {
        return array(
            array('someParameter', 'some value', 'someParameter'),
            array('some-parameter', 'some value', 'someParameter'),
        );
    }
    
    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function throwExceptionIfPassedParameterDosntExist()
    {
        $this->factory->addDefinition('stub', 'PHPPdf\Stub\ComplexAttribute\ComplexAttributeStub');
        $this->factory->create('stub', array('color' => '#cccccc', 'unexisted-parameter' => 'value'));
    }

    /**
     * @test
     * @expectedException PHPPdf\Core\ComplexAttribute\Exception\DefinitionNotFoundException
     */
    public function throwExceptionIfDefinitionDosntFound()
    {
        $this->factory->create('stub');
    }

    /**
     * @test
     */
    public function unserializedFactoryIsCopyOfSerializedFactory()
    {
        $this->factory->addDefinition('stub1', 'PHPPdf\Stub\ComplexAttribute\ComplexAttributeStub');
        $this->factory->addDefinition('stub2', 'PHPPdf\Stub\ComplexAttribute\ComplexAttributeStub');
        
        $this->factory->create('stub1', array('color' => '#ffffff'));

        $unserializedFactory = unserialize(serialize($this->factory));

        $unserializedDefinitions = $this->invokeMethod($unserializedFactory, 'getDefinitions');
        $definitions = $this->invokeMethod($this->factory, 'getDefinitions');

        $this->assertEquals($definitions, $unserializedDefinitions);
    }
}