<?php

namespace PHPPdf\Test\Core\Node;

use PHPPdf\Core\Node\NodeFactory,
    PHPPdf\Core\Node\Container;

class NodeFactoryTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $factory;

    public function setUp()
    {
        $this->factory = new NodeFactory();
    }

    /**
     * @test
     */
    public function nodeCreating()
    {
        $mock = $this->getMock('PHPPdf\Core\Node\Node', array('copy'));

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
     * @expectedException PHPPdf\Core\Exception\UnregisteredNodeException
     */
    public function creatingNotExistedNode()
    {
        $this->factory->create('key');
    }

    /**
     * @test
     * @expectedException PHPPdf\Core\Exception\UnregisteredNodeException
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
    public function invokeNodeMethodOnCreation()
    {
        $key = 'key';
        
        $invokeMethodName = 'setMarginLeft';
        $invokeMethodArg = 12;
        $invokeMethodArgTag = 'tag';
        
        $prototype = $this->getMock('PHPPdf\Core\Node\Container', array('copy'));
        $product = $this->getMock('PHPPdf\Core\Node\Container', array($invokeMethodName));
        
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
    
    /**
     * @test
     */
    public function addNodeAliases()
    {
        $prototype = new Container();
        $aliases = array('alias1', 'alias2');
        $key = 'key';
        $this->factory->addPrototype($key, $prototype, array(), $aliases);
        
        foreach($aliases as $alias)
        {
            $this->assertTrue($prototype === $this->factory->getPrototype($alias));
            
            $copy = $this->factory->create($alias);
        }
    }
}