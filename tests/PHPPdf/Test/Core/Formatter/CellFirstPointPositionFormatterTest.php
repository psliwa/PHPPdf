<?php

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Formatter\CellFirstPointPositionFormatter,
    PHPPdf\Core\Document,
    PHPPdf\Core\Point;

class CellFirstPointPositionFormatterTest extends \PHPPdf\PHPUnit\Framework\TestCase
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

        $parent = $this->getMock('PHPPdf\Core\Node\Container', array('getFirstPoint'));
        $parent->expects($this->atLeastOnce())
               ->method('getFirstPoint')
               ->will($this->returnValue($firstPoint));

        $boundary = $this->getMock('PHPPdf\Core\Boundary', array('setNext'));
        $boundary->expects($this->once())
                 ->method('setNext')
                 ->with($firstPoint);

        $node = $this->getMock('PHPPdf\Core\Node\Container', array('getParent', 'getBoundary'));
        $node->expects($this->atLeastOnce())
              ->method('getParent')
              ->will($this->returnValue($parent));
        $node->expects($this->atLeastOnce())
              ->method('getBoundary')
              ->will($this->returnValue($boundary));

        $this->formatter->format($node, $this->createDocumentStub());
    }
}