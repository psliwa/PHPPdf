<?php

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Document,
    PHPPdf\Core\Point,
    PHPPdf\Core\Boundary,
    PHPPdf\Core\Formatter\TextResetPositionFormatter;

class TextResetPositionFormatterTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $formatter;

    public function setUp()
    {
        $this->formatter = new TextResetPositionFormatter();
    }

    /**
     * @test
     */
    public function clearBoundaryAndAddOldFirstPoint()
    {
        $nodeMock = $this->getMock('\PHPPdf\Core\Node\Text', array('getBoundary'));

        $boundary = new Boundary();
        $boundary->setNext(0, 100)
                 ->setNext(100, 100)
                 ->setNext(100, 0)
                 ->setNext(0, 0)
                 ->close();

        $firstPoint = $boundary->getFirstPoint();

        $nodeMock->expects($this->atLeastOnce())
                  ->method('getBoundary')
                  ->will($this->returnValue($boundary));

        $this->formatter->format($nodeMock, $this->createDocumentStub());

        $this->assertFalse($boundary->isClosed());
        $this->assertEquals($firstPoint, $boundary->getFirstPoint());
        $this->assertEquals(1, count($boundary));
    }
}