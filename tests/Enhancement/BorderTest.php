<?php

use PHPPdf\Enhancement\Border;
use PHPPdf\Util\Boundary;
use PHPPdf\Glyph\Page;
use PHPPdf\Util\Point;
use PHPPdf\Glyph\GraphicsContext;

class BorderTest extends PHPUnit_Framework_TestCase
{
    private $border;

    public function setUp()
    {
        $this->border = new Border();
    }

    /**
     * @test
     */
    public function genericEnhance()
    {
        $x = 0;
        $y = 100;
        $width = 100;
        $height = 50;

        $pageMock = $this->getPageMock(array(-0.5, 100, 100, 0, 0), array(100, 100, 50, 50, 100.5));

        $glyphMock = $this->getGlyphMock($x, $y, $width, $height);

        $this->border->enhance($pageMock, $glyphMock);
    }

    private function getPageMock($x, $y)
    {
        $gcMock = $this->getMock('PHPPdf\Glyph\GraphicsContext', array('drawPolygon', 'setLineDashingPattern', 'setLineWidth'), array(), '', false);
        $gcMock->expects($this->once())
                 ->method('drawPolygon')
                 ->with($x, $y, \Zend_Pdf_Page::SHAPE_DRAW_STROKE);

        $pageMock = $this->getEmptyPageMock($gcMock);

        return $pageMock;
    }

    private function getEmptyPageMock($graphicsContext)
    {
        $pageMock = $this->getMock('PHPPdf\Glyph\Page', array('getGraphicsContext'));

        $pageMock->expects($this->atLeastOnce())
                 ->method('getGraphicsContext')
                 ->will($this->returnValue($graphicsContext));

        return $pageMock;
    }

    private function getGlyphMock($x, $y, $width, $height)
    {
        $boundaryMock = $this->getBoundaryStub($x, $y, $width, $height);

        $glyphMock = $this->getMock('PHPPdf\Glyph\AbstractGlyph', array('getBoundary', 'getWidth', 'getHeight'));
        
        $glyphMock->expects($this->atLeastOnce())
                  ->method('getBoundary')
                  ->will($this->returnValue($boundaryMock));

        $glyphMock->expects($this->any())
                  ->method('getWidth')
                  ->will($this->returnValue($width));

        $glyphMock->expects($this->any())
                  ->method('getHeight')
                  ->will($this->returnValue($height));

        return $glyphMock;
    }

    private function getBoundaryStub($x, $y, $width, $height)
    {
        $boundary = new Boundary();

        $boundary->setNext($x, $y)
                 ->setNext($x+$width, $y)
                 ->setNext($x+$width, $y-$height)
                 ->setNext($x, $y-$height)
                 ->close()
                ;

        return $boundary;
    }

    /**
     * @test
     * @dataProvider getTypes
     */
    public function settingBorderTypes($typePassed, $typeExcepted)
    {
        $border = new Border(null, $typePassed);
        $this->assertEquals($typeExcepted, $border->getType());
    }

    public function getTypes()
    {
        return array(
            array('left+right', Border::TYPE_LEFT | Border::TYPE_RIGHT),
            array(Border::TYPE_LEFT | Border::TYPE_RIGHT, Border::TYPE_LEFT | Border::TYPE_RIGHT),
        );
    }

    /**
     * @test
     */
    public function defaultBorderType()
    {
        $this->assertEquals(Border::TYPE_ALL, $this->border->getType());
    }

    /**
     * @test
     */
    public function drawingPartialBorder()
    {
        $gcMock = $this->getMock('PHPPdf\Glyph\GraphicsContext', array('drawLine', 'setLineDashingPattern', 'setLineWidth'), array(), '', false);

        //at(0) and at(1) for setLineDashingPattern and setLineWidth
        $gcMock->expects($this->at(2))
               ->method('drawLine')
               ->with(-0.5, 100, 50.5, 100);
        
        $gcMock->expects($this->at(3))
               ->method('drawLine')
               ->with(50.5, 50, -0.5, 50);

        $pageMock = $this->getEmptyPageMock($gcMock);

        $glyphMock = $this->getGlyphMock(0, 100, 50, 50);

        $border = new Border(null, Border::TYPE_TOP | Border::TYPE_BOTTOM);

        $border->enhance($pageMock, $glyphMock);
    }

    /**
     * @test
     */
    public function borderWithNotStandardSize()
    {
        $size = 2;

        $gcMock = $this->getMock('PHPPdf\Glyph\GraphicsContext', array('drawLine', 'setLineDashingPattern', 'setLineWidth'), array(), '', false);

        $gcMock->expects($this->once())
               ->method('setLineWidth')
               ->with($size);
        
        $gcMock->expects($this->once())
               ->method('drawLine')
               ->with(0, 2, 0, 11);

        $pageMock = $this->getEmptyPageMock($gcMock);

        $glyphMock = $this->getGlyphMock(0, 10, 5, 7);

        $border = new Border(null, Border::TYPE_LEFT, $size);

        $border->enhance($pageMock, $glyphMock);
    }

    /**
     * @test
     */
    public function fullRadiusBorder()
    {
        $radius = 50;

        $gcMock = $this->getMock('PHPPdf\Glyph\GraphicsContext', array('drawRoundedRectangle', 'setLineDashingPattern', 'setLineWidth'), array(), '', false);
        $gcMock->expects($this->once())
               ->method('drawRoundedRectangle')
               ->with(0, 70, 50, 100, $radius, Zend_Pdf_Page::SHAPE_DRAW_STROKE);

        $pageMock = $this->getEmptyPageMock($gcMock);

        $glyphMock = $this->getGlyphMock(0, 100, 50, 30);

        $border = new Border(null, Border::TYPE_ALL, 1, $radius);

        $border->enhance($pageMock, $glyphMock);
    }

    /**
     * @test
     */
    public function settingRadiusInStringStyle()
    {
        $border = new Border(null, Border::TYPE_ALL, 1, '5 5');

        $this->assertEquals(array(5, 5, 5, 5), $border->getRadius());
    }

    /**
     * @test
     */
    public function settingCustomizedDashingPatternInStringStyle()
    {
        $border = new Border(null, Border::TYPE_ALL, 1, null, '1 2 3');

        $this->assertEquals(array(1, 2, 3), $border->getStyle());
    }

    /**
     * @test
     * @dataProvider borderStyleProvider
     */
    public function borderStyle($style)
    {
        $gcMock = $this->getMock('PHPPdf\Glyph\GraphicsContext', array('setLineDashingPattern', 'drawLine', 'setLineWidth'), array(), '', false);

        $gcMock->expects($this->at(0))
               ->method('setLineDashingPattern')
               ->with($style);

        //at(1) for setLineWidth

        $gcMock->expects($this->at(2))
               ->method('drawLine')
               ->with(0, 69.5, 0, 100.5);

        $pageMock = $this->getEmptyPageMock($gcMock);

        $glyphMock = $this->getGlyphMock(0, 100, 50, 30);

        $border = new Border(null, Border::TYPE_LEFT, 1, null, $style);

        $border->enhance($pageMock, $glyphMock);
    }

    public function borderStyleProvider()
    {
        return array(
            array(Border::STYLE_SOLID),
            array(Border::STYLE_DOTTED),
        );
    }

    /**
     * @test
     * @dataProvider borderStyleByStringProvider
     */
    public function settingBorderStyleByString($style, $excepted)
    {
        $border = new Border(null, Border::TYPE_LEFT, 1, null, $style);
        $this->assertEquals($excepted, $border->getStyle());
    }

    public function borderStyleByStringProvider()
    {
        return array(
            array('solid', Border::STYLE_SOLID),
            array('dotted', Border::STYLE_DOTTED),
        );
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function throwExceptionIfBorderStyleIsInvalid()
    {
        new Border(null, Border::TYPE_LEFT, 1, null, 'invalid_style');
    }

    /**
     * @test
     */
    public function positionCorrectionInFullBorder()
    {
        $x = 0;
        $y = 100;
        $width = 50;
        $height = 30;
        $position = 2;
        $size = 1;
        
        $border = new Border(null, Border::TYPE_ALL, $size, null, Border::STYLE_SOLID, $position);

        $gcMock = $this->getMock('PHPPdf\Glyph\GraphicsContext', array('setLineDashingPattern', 'drawPolygon', 'setLineWidth'), array(), '', false);

        $glyphMock = $this->getGlyphMock($x, $y, $width, $height);
        $halfSize = $size/2;

        $gcMock->expects($this->once())
               ->method('drawPolygon')
               ->with(array($x-$halfSize-$position, $x+$width+$position, $x+$width+$position, $x-$position, $x-$position),
                      array($y+$position, $y+$position, $y-$height-$position, $y-$height-$position, $y+$halfSize+$position));

        $pageMock = $this->getEmptyPageMock($gcMock);

        $border->enhance($pageMock, $glyphMock);
    }

    /**
     * @test
     */
    public function positionCorrectionInPartialBorder()
    {
        $x = 0;
        $y = 100;
        $width = 50;
        $height = 30;

        $type = Border::TYPE_BOTTOM;
        $position = 2;
        $size = 1;
        $halfSize = $size/2;

        $border = new Border(null, $type, $size, null, Border::STYLE_SOLID, $position);

        $gcMock = $this->getMock('PHPPdf\Glyph\GraphicsContext', array('setLineDashingPattern', 'drawLine', 'setLineWidth'), array(), '', false);

        $glyphMock = $this->getGlyphMock($x, $y, $width, $height);

        $gcMock->expects($this->once())
               ->method('drawLine')
               ->with($x+$width+$position+$halfSize, $y-$height-$position, $x-$position-$halfSize, $y-$height-$position);

        $pageMock = $this->getEmptyPageMock($gcMock);

        $border->enhance($pageMock, $glyphMock);
    }
}