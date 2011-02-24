<?php

use PHPPdf\Glyph\Page,
    PHPPdf\Glyph\GraphicsContext;

class GraphicsContextTest extends TestCase
{
    /**
     * @test
     */
    public function clipRectangleWrapper()
    {
        $zendPageMock = $this->getMock('\Zend_Pdf_Page', array('clipRectangle'), array(), '', false);

        $x1 = 0;
        $x2 = 100;
        $y1 = 0;
        $y2 = 100;

        $zendPageMock->expects($this->once())
                     ->method('clipRectangle')
                     ->with($x1, $y1, $x2, $y2);

        $gc = new GraphicsContext($zendPageMock);

        $gc->clipRectangle($x1, $y1, $x2, $y2);
    }

    /**
     * @test
     */
    public function saveAndRestoreGSWrapper()
    {
        $zendPageMock = $this->getMock('\Zend_Pdf_Page', array('saveGS', 'restoreGS'), array(), '', false);

        $zendPageMock->expects($this->at(0))
                     ->method('saveGS');
        $zendPageMock->expects($this->at(1))
                     ->method('restoreGS');

        $gc = new GraphicsContext($zendPageMock);

        $gc->saveGS();
        $gc->restoreGS();
    }

    /**
     * @test
     */
    public function drawImageWrapper()
    {
        $x1 = 0;
        $x2 = 100;
        $y1 = 0;
        $y2 = 100;

        $zendPageMock = $this->getMock('\Zend_Pdf_Page', array('drawImage'), array(), '', false);
        $image = $this->getMock('\Zend_Pdf_Resource_Image', array());

        $zendPageMock->expects($this->once())
                     ->method('drawImage')
                     ->with($image, $x1, $y1, $x2, $y2);

        $gc = new GraphicsContext($zendPageMock);

        $gc->drawImage($image, $x1, $y1, $x2, $y2);
    }

    /**
     * @test
     */
    public function drawLineWrapper()
    {
        $x1 = 0;
        $x2 = 100;
        $y1 = 0;
        $y2 = 100;

        $zendPageMock = $this->getMock('\Zend_Pdf_Page', array('drawLine'), array(), '', false);

        $zendPageMock->expects($this->once())
                     ->method('drawLine')
                     ->with($x1, $y1, $x2, $y2);

        $gc = new GraphicsContext($zendPageMock);

        $gc->drawLine($x1, $y1, $x2, $y2);
    }

    /**
     * @test
     */
    public function setFontWrapper()
    {
        $zendPageMock = $this->getMock('\Zend_Pdf_Page', array('setFont'), array(), '', false);
        $zendFontMock = $this->getMock('\Zend_Pdf_Resource_Font');

        $fontMock = $this->getMock('\PHPPdf\Font\Font', array('getFont'), array(array()), '', false);
        $fontMock->expects($this->once())
                 ->method('getFont')
                 ->will($this->returnValue($zendFontMock));
        $size = 12;

        $zendPageMock->expects($this->once())
                     ->method('setFont')
                     ->with($zendFontMock, $size);

        $gc = new GraphicsContext($zendPageMock);

        $gc->setFont($fontMock, $size);
    }

    /**
     * @test
     * @dataProvider colorSetters
     */
    public function setColorsWrapper($method)
    {
        $zendPageMock = $this->getMock('\Zend_Pdf_Page', array($method), array(), '', false);
        $zendColor = $this->getMock('\Zend_Pdf_Color');

        $zendPageMock->expects($this->once())
                     ->method($method)
                     ->with($zendColor);

        $gc = new GraphicsContext($zendPageMock);

        $gc->$method($zendColor);

        //don't delegate if not necessary
        $gc->$method($zendColor);
    }

    public function colorSetters()
    {
        return array(
            array('setFillColor'),
            array('setLineColor'),
        );
    }

    /**
     * @test
     */
    public function drawPolygonWrapper()
    {
        $x = array(0, 100, 50);
        $y = array(0, 100, 50);
        $drawType = 1;

        $zendPageMock = $this->getMock('\Zend_Pdf_Page', array('drawPolygon'), array(), '', false);

        $zendPageMock->expects($this->once())
                     ->method('drawPolygon')
                     ->with($x, $y, $drawType);

        $gc = new GraphicsContext($zendPageMock);

        $gc->drawPolygon($x, $y, $drawType);
    }

    /**
     * @test
     */
    public function drawTextWrapper()
    {
        $x = 10;
        $y = 200;
        $text = 'some text';
        $encoding = 'utf-8';

        $zendPageMock = $this->getMock('\Zend_Pdf_Page', array('drawText'), array(), '', false);

        $zendPageMock->expects($this->once())
                     ->method('drawText')
                     ->with($text, $x, $y, $encoding);

        $gc = new GraphicsContext($zendPageMock);

        $gc->drawText($text, $x, $y, $encoding);
    }

    /**
     * @test
     */
    public function drawRoundedRectangleWrapper()
    {
        $x1 = 10;
        $y1 = 100;
        $x2 = 100;
        $y2 = 50;
        $radius = 0.5;
        $fillType = 1;

        $zendPageMock = $this->getMock('\Zend_Pdf_Page', array('drawRoundedRectangle'), array(), '', false);

        $zendPageMock->expects($this->once())
                     ->method('drawRoundedRectangle')
                     ->with($x1, $y1, $x2, $y2, $radius, $fillType);

        $gc = new GraphicsContext($zendPageMock);

        $gc->drawRoundedRectangle($x1, $y1, $x2, $y2, $radius, $fillType);
    }

    /**
     * @test
     */
    public function setLineWidthWrapper()
    {
        $width = 2.1;

        $zendPageMock = $this->getMock('\Zend_Pdf_Page', array('setLineWidth'), array(), '', false);

        $zendPageMock->expects($this->once())
                     ->method('setLineWidth')
                     ->with($width);

        $gc = new GraphicsContext($zendPageMock);

        $gc->setLineWidth($width);

        //don't delegate if not necessary
        $gc->setLineWidth($width);
    }

    /**
     * @test
     * @dataProvider lineDashingPatternProvider
     */
    public function setLineDashingPatternWrapper($pattern, $expected)
    {
        $zendPageMock = $this->getMock('\Zend_Pdf_Page', array('setLineDashingPattern'), array(), '', false);

        $zendPageMock->expects($this->once())
                     ->method('setLineDashingPattern')
                     ->with($expected);

        $gc = new GraphicsContext($zendPageMock);

        $gc->setLineDashingPattern($pattern);

        //don't delegate if not necessary
        $gc->setLineDashingPattern($pattern);
    }

    public function lineDashingPatternProvider()
    {
        return array(
            array(array(0), array(0)),
            array(GraphicsContext::DASHING_PATTERN_SOLID, 0),
            array(GraphicsContext::DASHING_PATTERN_DOTTED, array(1, 2))
        );
    }

    /**
     * @test
     */
    public function cachingGraphicsState()
    {
        $zendColor1 = $this->getMock('\Zend_Pdf_Color', array('instructions', 'getComponents'));
        $zendColor2 = $this->getMock('\Zend_Pdf_Color', array('instructions', 'getComponents'));
        $zendColor2->expects($this->any())
                   ->method('getComponents')
                   ->will($this->returnValue(array(9, 10, 11)));

        $zendPageMock = $this->getMock('\Zend_Pdf_Page', array('setLineDashingPattern', 'setLineWidth', 'setFillColor', 'setLineColor', 'saveGS', 'restoreGS'), array(), '', false);

        $zendPageMock->expects($this->at(0))
                     ->method('saveGS');
        $zendPageMock->expects($this->at(1))
                     ->method('setLineDashingPattern');        
        $zendPageMock->expects($this->at(2))
                     ->method('setLineWidth');
        $zendPageMock->expects($this->at(3))
                     ->method('setFillColor');        
        $zendPageMock->expects($this->at(4))
                     ->method('setLineColor');
        $zendPageMock->expects($this->at(5))
                     ->method('restoreGS');
        $zendPageMock->expects($this->at(6))
                     ->method('setLineDashingPattern');        
        $zendPageMock->expects($this->at(7))
                     ->method('setLineWidth');
        $zendPageMock->expects($this->at(8))
                     ->method('setFillColor');        
        $zendPageMock->expects($this->at(9))
                     ->method('setLineColor');
        $zendPageMock->expects($this->at(10))
                     ->method('setLineDashingPattern');
        $zendPageMock->expects($this->at(11))
                     ->method('setLineWidth');
        $zendPageMock->expects($this->at(12))
                     ->method('setFillColor');
        $zendPageMock->expects($this->at(13))
                     ->method('setLineColor');


        $gc = new GraphicsContext($zendPageMock);

        $gc->saveGS();
        //second loop pass do not change internal gc state
        for($i=0; $i<2; $i++)
        {
            $gc->setLineDashingPattern(array(1, 1));
            $gc->setLineWidth(1);
            $gc->setFillColor($zendColor1);
            $gc->setLineColor($zendColor1);
        }

        $gc->restoreGS();

        //second loop pass do not change internal gc state
        for($i=0; $i<2; $i++)
        {
            $gc->setLineDashingPattern(array(1, 1));
            $gc->setLineWidth(1);
            $gc->setFillColor($zendColor1);
            $gc->setLineColor($zendColor1);
        }

        //overriding by new values
        $gc->setLineDashingPattern(array(1, 2));
        $gc->setLineWidth(2);
        $gc->setFillColor($zendColor2);
        $gc->setLineColor($zendColor2);
    }
}