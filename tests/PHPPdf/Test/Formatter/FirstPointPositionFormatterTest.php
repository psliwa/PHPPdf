<?php

namespace PHPPdf\Test\Formatter;

use PHPPdf\Formatter\FirstPointPositionFormatter,
    PHPPdf\Document;

class FirstPointPositionFormatterTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $formatter;

    public function setUp()
    {
        $this->formatter = new FirstPointPositionFormatter();
    }

    /**
     * @test
     * @dataProvider attributeProvider
     */
    public function designateFirstPointIfNodeHasntPreviousSibling($parentFirstPoint, $marginLeft, $marginTop)
    {
        $parent = $this->getMock('PHPPdf\Node\Container', array('getStartDrawingPoint'));

        $parent->expects($this->atLeastOnce())
               ->method('getStartDrawingPoint')
               ->will($this->returnValue($parentFirstPoint));

        $node = $this->getMock('PHPPdf\Node\Container', array('getParent', 'getPreviousSibling', 'getMarginLeft', 'getMarginTop'));

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

        $this->formatter->format($node, new Document());

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
}