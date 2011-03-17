<?php

use PHPPdf\Document,
    PHPPdf\Util\Point,
    PHPPdf\Util\Boundary,
    PHPPdf\Formatter\TextResetPositionFormatter;

class TextResetPositionFormatterTest extends PHPUnit_Framework_TestCase
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
        $glyphMock = $this->getMock('\PHPPdf\Glyph\Text', array('getBoundary'));

        $boundary = new Boundary();
        $boundary->setNext(0, 100)
                 ->setNext(100, 100)
                 ->setNext(100, 0)
                 ->setNext(0, 0)
                 ->close();

        $firstPoint = $boundary->getFirstPoint();

        $glyphMock->expects($this->atLeastOnce())
                  ->method('getBoundary')
                  ->will($this->returnValue($boundary));

        $this->formatter->format($glyphMock, new Document());

        $this->assertFalse($boundary->isClosed());
        $this->assertEquals($firstPoint, $boundary->getFirstPoint());
        $this->assertEquals(1, count($boundary));
    }
}