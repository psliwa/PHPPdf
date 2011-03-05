<?php

use PHPPdf\Glyph\Factory as GlyphFactory,
    PHPPdf\Glyph\Container;

class GlyphFactoryTest extends PHPUnit_Framework_TestCase
{
    private $factory;

    public function setUp()
    {
        $this->factory = new GlyphFactory();
    }

    /**
     * @test
     */
    public function glyphCreating()
    {
        $mock = $this->getMock('PHPPdf\Glyph\AbstractGlyph', array('copy'));

        $mock->expects($this->once())
             ->method('copy')
             ->will($this->returnValue($mock));

        $this->factory->addPrototype('name', $mock);
        $this->factory->create('name');
    }

    /**
     * @test
     */
    public function validPrototypeAdding()
    {
        $key = 'key';

        $this->assertFalse($this->factory->hasPrototype($key));

        $prototype = new Container();
        $this->factory->addPrototype($key, $prototype);

        $this->assertTrue($this->factory->hasPrototype($key));
        $this->assertEquals($prototype, $this->factory->getPrototype($key));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function creatingNotExistedGlyph()
    {
        $this->factory->create('key');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function gettingNotExistingPrototype()
    {
        $this->factory->getPrototype('key');
    }

    /**
     * @test
     */
    public function unserializedFactoryIsCopyOfSerializedFactory()
    {
        $key = 'key';
        $prototype = new Container();
        $this->factory->addPrototype($key, $prototype);

        $unserializedFactory = unserialize(serialize($this->factory));

        $this->assertEquals($this->factory->getPrototype($key), $unserializedFactory->getPrototype($key));
    }
}