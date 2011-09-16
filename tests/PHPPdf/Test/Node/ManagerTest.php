<?php

namespace PHPPdf\Test\Node;

use PHPPdf\Node\Container;

use PHPPdf\Node\Manager;

class ManagerTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $manager;
    
    public function setUp()
    {
        $this->manager = new Manager();
    }
    
    /**
     * @test
     */
    public function registeringPopulatesEmptyWrappers()
    {
        $wrapper = $this->manager->get('id');
        
        $this->assertNull($wrapper->getNode());
        
        $node = new Container();        
        $this->manager->register('id', $node);
        
        $this->assertEquals($node, $wrapper->getNode());
    }
    
    /**
     * @test
     */
    public function attachNodeAsManagable()
    {
        for($i=0; $i<2; $i++)
        {
            $node = $this->getMockBuilder('PHPPdf\Node\Node')
                         ->setMethods(array('flush'))
                         ->getMock();
            $node->expects($this->once())
                 ->method('flush');
            $this->manager->attach($node);
        }
        
        $this->manager->flush();
        $this->manager->flush();
    }
}