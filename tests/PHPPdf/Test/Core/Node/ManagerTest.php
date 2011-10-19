<?php

namespace PHPPdf\Test\Core\Node;

use PHPPdf\Core\Node\Container;

use PHPPdf\Core\Node\Manager;

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
}