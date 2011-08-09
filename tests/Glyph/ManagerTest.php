<?php

namespace PHPPdf\Test\Glyph;

use PHPPdf\Glyph\Container;

use PHPPdf\Glyph\Manager;

class ManagerTest extends \TestCase
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
        
        $this->assertNull($wrapper->getGlyph());
        
        $glyph = new Container();        
        $this->manager->register('id', $glyph);
        
        $this->assertEquals($glyph, $wrapper->getGlyph());
    }
}