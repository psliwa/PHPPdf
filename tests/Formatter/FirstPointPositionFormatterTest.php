<?php

use PHPPdf\Formatter\FirstPointPositionFormatter,
    PHPPdf\Document;

class FirstPointPositionFormatterTest extends TestCase
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
    public function designateFirstPointIfGlyphHasntPreviousSibling($parentFirstPoint, $marginLeft, $marginTop)
    {
        $parent = $this->getMock('PHPPdf\Glyph\Container', array('getStartDrawingPoint'));

        $parent->expects($this->atLeastOnce())
               ->method('getStartDrawingPoint')
               ->will($this->returnValue($parentFirstPoint));

        $glyph = $this->getMock('PHPPdf\Glyph\Container', array('getParent', 'getPreviousSibling', 'getMarginLeft', 'getMarginTop'));

        $glyph->expects($this->atLeastOnce())
              ->method('getParent')
              ->will($this->returnValue($parent));
        $glyph->expects($this->once())
              ->method('getPreviousSibling')
              ->will($this->returnValue(null));
        $glyph->expects($this->once())
              ->method('getMarginLeft')
              ->will($this->returnValue($marginLeft));
        $glyph->expects($this->once())
              ->method('getMarginTop')
              ->will($this->returnValue($marginTop));

        $this->formatter->format($glyph, new Document());

        $parentFirstPoint[0] += $marginLeft;
        $parentFirstPoint[1] -= $marginTop;
        $this->assertEquals($parentFirstPoint, $glyph->getBoundary()->getFirstPoint()->toArray());
    }

    public function attributeProvider()
    {
        return array(
            array(array(0, 600), 0, 0),
            array(array(0, 600), 10, 10),
        );
    }
}