<?php

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Node\Container;

use PHPPdf\ObjectMother\NodeObjectMother;

use PHPPdf\Core\Formatter\FirstPointPositionFormatter,
    PHPPdf\Core\Document;

class FirstPointPositionFormatterTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $formatter;
    private $objectMother;

    public function setUp()
    {
        $this->formatter = new FirstPointPositionFormatter();
        $this->objectMother = new NodeObjectMother($this);
    }

    /**
     * @test
     * @dataProvider attributeProvider
     */
    public function designateFirstPointIfNodeHasntPreviousSibling($parentFirstPoint, $marginLeft, $marginTop)
    {
        $parent = $this->getMock('PHPPdf\Core\Node\Container', array('getStartDrawingPoint'));

        $parent->expects($this->atLeastOnce())
               ->method('getStartDrawingPoint')
               ->will($this->returnValue($parentFirstPoint));

        $node = $this->getMock('PHPPdf\Core\Node\Container', array('getParent', 'getPreviousSibling', 'getMarginLeft', 'getMarginTop'));

        $node->expects($this->atLeastOnce())
              ->method('getParent')
              ->will($this->returnValue($parent));
        $node->expects($this->once())
              ->method('getPreviousSibling')
              ->will($this->returnValue(null));
        $node->expects($this->once())
              ->method('getMarginLeft')
              ->will($this->returnValue($marginLeft));
        $node->expects($this->once())
              ->method('getMarginTop')
              ->will($this->returnValue($marginTop));

        $this->formatter->format($node, $this->createDocumentStub());

        $parentFirstPoint[0] += $marginLeft;
        $parentFirstPoint[1] -= $marginTop;
        $this->assertEquals($parentFirstPoint, $node->getBoundary()->getFirstPoint()->toArray());
    }

    public function attributeProvider()
    {
        return array(
            array(array(0, 600), 0, 0),
            array(array(0, 600), 10, 10),
        );
    }
    
    /**
     * @test
     * @dataProvider booleanProvider
     */
    public function properlyLineBreaking($lineBreakOfPreviousSibling)
    {
        $parentFirstPoint = array(0, 100);
        $lineHeight = 20;
        
        $parent = $this->getMock('PHPPdf\Core\Node\Container', array('getStartDrawingPoint'));
        
        $parent->expects($this->atLeastOnce())
               ->method('getStartDrawingPoint')
               ->will($this->returnValue($parentFirstPoint));
               
        $previousSibling = new Container();
        $boundary = $this->objectMother->getBoundaryStub($parentFirstPoint[0], $parentFirstPoint[1], 100, 0);
        $this->invokeMethod($previousSibling, 'setBoundary', array($boundary));
        $previousSibling->setAttribute('line-break', $lineBreakOfPreviousSibling);

        $node = $this->getMock('PHPPdf\Core\Node\Container', array('getParent', 'getPreviousSibling', 'getLineHeightRecursively'));
        $node->setAttribute('line-break', true);
        $node->expects($this->atLeastOnce())
             ->method('getParent')
             ->will($this->returnValue($parent));
        $node->expects($this->atLeastOnce())
             ->method('getPreviousSibling')
             ->will($this->returnValue($previousSibling));
        $node->expects($this->any())
             ->method('getLineHeightRecursively')
             ->will($this->returnValue($lineHeight));
        
        $this->formatter->format($node, $this->createDocumentStub());
        
        //break line only when previous sibling also has line-break attribute on
        $expectedYCoord = $lineBreakOfPreviousSibling ? ($parentFirstPoint[1] - $lineHeight) : $parentFirstPoint[1];
        
        $this->assertEquals($expectedYCoord, $node->getFirstPoint()->getY());
    }
    
    public function booleanProvider()
    {
        return array(
            array(true),
            array(false),
        );
    }
}