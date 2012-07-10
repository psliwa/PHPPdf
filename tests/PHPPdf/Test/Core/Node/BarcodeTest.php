<?php

namespace PHPPdf\Test\Core\Node;

use PHPPdf\Core\DrawingTaskHeap;
use PHPPdf\ObjectMother\NodeObjectMother;
use PHPPdf\Core\Node\Page;
use PHPPdf\Core\Node\Barcode;
use PHPPdf\PHPUnit\Framework\TestCase;

class BarcodeTest extends TestCase
{
    private $barcode;
    private $objectMother;
    private $gc;
    private $page;
    
    public function setUp()
    {
        $this->barcode = new Barcode();
        $this->objectMother = new NodeObjectMother($this);
        $this->gc = $this->getMock('PHPPdf\Core\Engine\GraphicsContext');
        $this->page = new Page();
        $this->invokeMethod($this->page, 'setGraphicsContext', array($this->gc));
        $this->barcode->setParent($this->page);
    }
    
    /**
     * @test
     * @dataProvider drawBarcodeInGraphicsContextProvider
     */
    public function drawBarcodeInGraphicsContext($x, $y, $width, $height, $barHeight, $barcodeText, $drawText, $barcodeType, $fontType, $fontSize, $color, $withChecksum, $orientation, $barThinWidth, $barThickWidth, $factor)
    {               
        $boundary = $this->objectMother->getBoundaryStub($x, $y, $width, $height);
        $this->invokeMethod($this->barcode, 'setBoundary', array($boundary));
        $font = $this->getMock('PHPPdf\Core\Engine\Font');
        $fontPath = 'path';
        
        $document = $this->getMockBuilder('PHPPdf\Core\Document')
                         ->disableOriginalConstructor()
                         ->setMethods(array('getFont', 'getColorFromPalette'))
                         ->getMock();
                         
        $document->expects($this->once())
                 ->method('getFont')
                 ->with($fontType)
                 ->will($this->returnValue($font));
        $document->expects($this->once())
                 ->method('getColorFromPalette')
                 ->with($color)
                 ->will($this->returnValue($color));
        
        $this->gc->expects($this->once())
                 ->method('drawBarcode')
                 ->with($x, $y, $this->validateByCallback(function($barcode, TestCase $test) use($barcodeText, $drawText, $barcodeType, $fontPath, $fontSize, $color, $barHeight, $withChecksum, $orientation, $barThinWidth, $barThickWidth, $factor){
                     $test->assertInstanceOf('Zend\Barcode\Object\ObjectInterface', $barcode);
                     $test->assertTrue(stripos(get_class($barcode), $barcodeType) !== false);
                     $test->assertEquals($barcodeText, $barcode->getText());
                     $test->assertEquals($fontPath, $barcode->getFont());
                     $test->assertEquals($fontSize, $barcode->getFontSize());
                     $test->assertEquals(hexdec($color), $barcode->getForeColor());
                     $test->assertEquals($drawText, $barcode->getDrawText());
                     $test->assertEquals($barHeight, $barcode->getBarHeight());
                     $test->assertEquals($withChecksum, $barcode->getWithChecksum());
                     $test->assertEquals($withChecksum, $barcode->getWithChecksumInText());
                     $test->assertEquals((int) $orientation, $barcode->getOrientation());
                     $test->assertEquals($barThinWidth, $barcode->getBarThinWidth());
                     $test->assertEquals($barThickWidth, $barcode->getBarThickWidth());
                     $test->assertEquals($factor, $barcode->getFactor());
                 }, $this));
     
        $font->expects($this->once())
             ->method('getCurrentResourceIdentifier')
             ->will($this->returnValue($fontPath));
                 
        $this->barcode->setAttribute('type', $barcodeType);
        $this->barcode->setAttribute('code', $barcodeText);
        $this->barcode->setAttribute('font-type', $fontType);
        $this->barcode->setAttribute('font-size', $fontSize);
        $this->barcode->setAttribute('color', $color);
        $this->barcode->setAttribute('draw-code', $drawText);
        $this->barcode->setAttribute('bar-height', $barHeight);
        $this->barcode->setAttribute('with-checksum', $withChecksum);
        $this->barcode->setAttribute('with-checksum-in-text', $withChecksum);
        $this->barcode->setAttribute('rotate', $orientation);
        $this->barcode->setAttribute('bar-thin-width', $barThinWidth);
        $this->barcode->setAttribute('bar-thick-width', $barThickWidth);
        $this->barcode->setAttribute('factor', $factor);
        
        $tasks = new DrawingTaskHeap();
        $this->barcode->collectOrderedDrawingTasks($document, $tasks);
        
        foreach($tasks as $task)
        {
            $task->invoke();
        }
    }
    
    public function drawBarcodeInGraphicsContextProvider()
    {
        return array(
            array(100, 30, 50, 70, 55, 'abc', true, 'code128', 'some-font', 12, '#cccccc', true, '45deg', 12, 21, 2),
        );
    }
}