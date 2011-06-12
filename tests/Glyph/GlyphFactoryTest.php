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
        $mock = $this->getMock('PHPPdf\Glyph\Glyph', array('copy'));

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
    
    /**
     * @test
     */
    public function invokeGlyphMethodOnCreation()
    {
        $key = 'key';
        
        $invokeMethodName = 'setMarginLeft';
        $invokeMethodArg = 12;
        $invokeMethodArgTag = 'tag';
        
        $prototype = $this->getMock('PHPPdf\Glyph\Container', array('copy'));
        $product = $this->getMock('PHPPdf\Glyph\Container', array($invokeMethodName));
        
        $prototype->expects($this->once())
                  ->method('copy')
                  ->will($this->returnValue($product));
                  
        $product->expects($this->once())
                ->method($invokeMethodName)
                ->with($invokeMethodArg);                  
        
        $this->factory->addPrototype($key, $prototype, array($invokeMethodName => $invokeMethodArgTag));
        $this->factory->addInvokeArg($invokeMethodArgTag, $invokeMethodArg);
        
        $this->assertTrue($product === $this->factory->create($key));        
    }
}