<?php

require_once __DIR__.'/../Stub/Enhancement/EnhancementStub.php';

use PHPPdf\Enhancement\Factory as EnhancementFactory;

class EnhancementFactoryTest extends PHPUnit_Framework_TestCase
{
    private $factory;

    public function setUp()
    {
        $this->factory = new EnhancementFactory();
    }

    /**
     * @test
     */
    public function setDefinitionOfEnhancement()
    {
        $this->assertFalse($this->factory->hasDefinition('stub'));
        $this->factory->addDefinition('stub', 'EnhancementStub');
        $this->assertTrue($this->factory->hasDefinition('stub'));
    }

    /**
     * @test
     */
    public function getParameterNames()
    {
        $this->factory->addDefinition('stub', 'EnhancementStub');
        $parameters = $this->factory->getParameters('stub');

        $this->assertEquals(array('color', 'someParameter'), $parameters);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function requiredParametersMustBePassed()
    {
        $this->factory->addDefinition('stub', 'EnhancementStub');
        $this->factory->create('stub', array());
    }

    /**
     * @test
     */
    public function createUsingValidParameters()
    {
        $this->factory->addDefinition('stub', 'EnhancementStub');
        $enhancement = $this->factory->create('stub', array('color' => '#bbbbbb', 'someParameter' => 'dupa'));

        $this->assertNotNull($enhancement);
        $this->assertInstanceOf('EnhancementStub', $enhancement);
    }

    /**
     * @test
     * @expectedException PHPPdf\Enhancement\Exception\DefinitionNotFoundException
     */
    public function throwExceptionIfDefinitionDosntFound()
    {
        $this->factory->create('stub');
    }
}