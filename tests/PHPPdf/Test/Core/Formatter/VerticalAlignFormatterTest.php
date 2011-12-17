<?php

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Node\Container;
use PHPPdf\ObjectMother\NodeObjectMother;
use PHPPdf\Core\Document,
    PHPPdf\Core\Formatter\VerticalAlignFormatter;

class VerticalAlignFormatterTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $formatter;
    private $document;
    
    private $objectMother;
    
    protected function init()
    {
        $this->objectMother = new NodeObjectMother($this);
    }
    
    public function setUp()
    {
        $this->formatter = new VerticalAlignFormatter();
        $this->document = $this->createDocumentStub();
    }
    
    /**
     * @test
     * @dataProvider alignSingleNodeProvider
     */
    public function alignSingleNode($parentHeight, $childHeight, $childYCoord, $align, $expectedYCoord)
    {
        $container = new Container();
        $this->invokeMethod($container, 'setBoundary', array($this->objectMother->getBoundaryStub(0, $parentHeight, 500, $parentHeight)));
        
        $child = new Container();
        $this->invokeMethod($child, 'setBoundary', array($this->objectMother->getBoundaryStub(0, $childYCoord, 500, $childHeight)));
        $container->add($child);
        
        $container->setAttribute('vertical-align', $align);     

        $this->formatter->format($container, $this->document);
        
        $this->assertEquals($expectedYCoord, $child->getFirstPoint()->getY());
    }
    
    public function alignSingleNodeProvider()
    {
        return array(
            array(500, 300, 500, 'top', 500),
            array(500, 300, 500, 'middle', 400),
            array(500, 300, 500, 'bottom', 300),
        );
    }
}