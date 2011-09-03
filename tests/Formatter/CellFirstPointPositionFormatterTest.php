<?php

use PHPPdf\Formatter\CellFirstPointPositionFormatter,
    PHPPdf\Document,
    PHPPdf\Util\Point;

class CellFirstPointPositionFormatterTest extends TestCase
{
    private $formatter;

    public function setUp()
    {
        $this->formatter = new CellFirstPointPositionFormatter();
    }

    /**
     * @test
     */
    public function setFirstPointAsFirstPointOfParent()
    {
        $firstPoint = Point::getInstance(0, 500);

        $parent = $this->getMock('PHPPdf\Node\Container', array('getFirstPoint'));
        $parent->expects($this->atLeastOnce())
               ->method('getFirstPoint')
               ->will($this->returnValue($firstPoint));

        $boundary = $this->getMock('PHPPdf\Util\Boundary', array('setNext'));
        $boundary->expects($this->once())
                 ->method('setNext')
                 ->with($firstPoint);

        $node = $this->getMock('PHPPdf\Node\Container', array('getParent', 'getBoundary'));
        $node->expects($this->atLeastOnce())
              ->method('getParent')
              ->will($this->returnValue($parent));
        $node->expects($this->atLeastOnce())
              ->method('getBoundary')
              ->will($this->returnValue($boundary));

        $this->formatter->format($node, new Document());
    }
}