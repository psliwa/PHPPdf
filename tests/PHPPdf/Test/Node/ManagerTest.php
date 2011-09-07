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
    public function registeringPopulateEmptyWrappers()
    {
        $wrapper = $this->manager->get('id');
        
        $this->assertNull($wrapper->getNode());
        
        $node = new Container();        
        $this->manager->register('id', $node);
        
        $this->assertEquals($node, $wrapper->getNode());
    }
}