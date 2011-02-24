<?php

use PHPPdf\Document;
use PHPPdf\Glyph\AbstractGlyph;
use PHPPdf\Glyph\Container;
use PHPPdf\Formatter\StandardDimensionFormatter;

class StandardDimensionFormatterTest extends PHPUnit_Framework_TestCase
{
    private $formatter;

    public function setUp()
    {
        $this->formatter = new StandardDimensionFormatter(new Document());
    }

    /**
     * @test
     */
    public function glyphFormatter()
    {
        $glyph = $this->getMock('PHPPdf\Glyph\AbstractGlyph', array('getWidth', 'getHeight', 'setWidth', 'setHeight'));
        $glyph->expects($this->atLeastOnce())
              ->method('getWidth')
              ->will($this->returnValue(120));
        $glyph->expects($this->atLeastOnce())
              ->method('getHeight')
              ->will($this->returnValue(140));
        $glyph->expects($this->once())
              ->method('setWidth')
              ->with($this->equalTo(120));
        $glyph->expects($this->once())
              ->method('setHeight')
              ->with($this->equalTo(140));

        $this->formatter->preFormat($glyph);
        $this->formatter->postFormat($glyph);
    }

    /**
     * @test
     */
    public function setZeroWidthGlyphsWithFloat()
    {
        $glyph = $this->getMock('PHPPdf\Glyph\Container', array('getWidth', 'setWidth', 'getFloat'));

        $glyph->expects($this->atLeastOnce())
              ->method('getWidth')
              ->will($this->returnValue(null));
        $glyph->expects($this->atLeastOnce())
              ->method('setWidth')
              ->with($this->equalTo(0));
        $glyph->expects($this->atLeastOnce())
              ->method('getFloat')
              ->will($this->returnValue('left'));

        $this->formatter->preFormat($glyph);
        $this->formatter->postFormat($glyph);
    }
}