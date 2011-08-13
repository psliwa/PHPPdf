<?php

use PHPPdf\Glyph\Glyph;
use PHPPdf\Document;
use PHPPdf\Util\Point;
use PHPPdf\Glyph\Paragraph\LinePart;

class LinePartTest extends TestCase
{
    /**
     * @test
     * @dataProvider drawingDataProvider
     */
    public function drawLinePartUsingTextGlyphAttributes($fontSize, $lineHeightOfText, $textDecoration, $expectedLineDecorationYCoord)
    {
        $encodingStub = 'utf-16';
        $colorStub = $this->getMockBuilder('PHPPdf\Engine\Color')
                          ->getMock();
        $fontStub = $this->getMockBuilder('PHPPdf\Engine\Font')
                         ->disableOriginalConstructor()
                         ->getMock();
        $words = 'some words';
        $startPoint = Point::getInstance(100, 120);

        $documentStub = new Document();
        $xTranslationInLine = 5;
        $linePartWidth = 100;
        
        $heightOfLine = 18;        
        
        $text = $this->getMockBuilder('PHPPdf\Glyph\Text')
                     ->setMethods(array('getFont', 'getAttribute', 'getRecurseAttribute', 'getGraphicsContext', 'getEncoding', 'getFontSize', 'getTextDecorationRecursively'))
                     ->getMock();
                         
        $text->expects($this->atLeastOnce())
             ->method('getFont')
             ->will($this->returnValue($fontStub));
             
        $text->expects($this->atLeastOnce())
             ->method('getFontSize')
             ->will($this->returnValue($fontSize));
             
        $text->expects($this->atLeastOnce())
             ->method('getRecurseAttribute')
             ->with('color')
             ->will($this->returnValue($colorStub));
             
        $text->expects($this->atLeastOnce())
             ->method('getAttribute')
             ->with('line-height')
             ->will($this->returnValue($lineHeightOfText));
             
        $text->expects($this->atLeastOnce())
             ->method('getEncoding')
             ->will($this->returnValue($encodingStub));
             
        $text->expects($this->atLeastOnce())
             ->method('getTextDecorationRecursively')
             ->will($this->returnValue($textDecoration));
             
        $gc = $this->getMockBuilder('PHPPdf\Engine\GraphicsContext')
        		   ->getMock();

	    $expectedXCoord = $startPoint->getX() + $xTranslationInLine;
	    $expectedYCoord = $startPoint->getY() - $fontSize - ($heightOfLine - $lineHeightOfText);
	    
        $gc->expects($this->once())
           ->method('drawText')
           ->with($words, $expectedXCoord, $expectedYCoord, $encodingStub);
           
        $gc->expects($this->once())
           ->method('setFont')
           ->with($fontStub, $fontSize);
           
        $gc->expects($this->once())
           ->method('setFillColor')
           ->with($colorStub);
           
        $gc->expects($this->once())
           ->method('saveGs');
        $gc->expects($this->once())
           ->method('restoreGS');
           
        if($expectedLineDecorationYCoord === false)
        {
            $gc->expects($this->never())
               ->method('drawLine');
        }
        else
        {
            $expectedYCoord = $expectedYCoord + $expectedLineDecorationYCoord;
            $gc->expects($this->once())
               ->method('setLineColor')
               ->id('color')
               ->with($colorStub);
               
            $gc->expects($this->once())
               ->method('setLineWidth')
               ->id('line')
               ->with(0.5);

            $gc->expects($this->once())
               ->after('color')
               ->method('drawLine')
               ->with($expectedXCoord, $expectedYCoord, $expectedXCoord + $linePartWidth, $expectedYCoord);
        }

        $text->expects($this->atLeastOnce())
             ->method('getGraphicsContext')
             ->will($this->returnValue($gc));
             
        $line = $this->getMockBuilder('PHPPdf\Glyph\Paragraph\Line')
                     ->setMethods(array('getFirstPoint', 'getHeight'))
                     ->disableOriginalConstructor()
                     ->getMock();
        $line->expects($this->atLeastOnce())
             ->method('getFirstPoint')
             ->will($this->returnValue($startPoint));
             
        $line->expects($this->atLeastOnce())
             ->method('getHeight')
             ->will($this->returnValue($heightOfLine));
        
        $linePart = new LinePart($words, $linePartWidth, $xTranslationInLine, $text);
        $linePart->setLine($line);
        
        $tasks = $linePart->getDrawingTasks($documentStub);
        
        foreach($tasks as $task)
        {
            $task->invoke();
        }
    }
    
    public function drawingDataProvider()
    {
        return array(
            array(11, 15, Glyph::TEXT_DECORATION_NONE, false),
            array(11, 15, Glyph::TEXT_DECORATION_UNDERLINE, -1),
            array(18, 15, Glyph::TEXT_DECORATION_LINE_THROUGH, 6),
            array(12, 15, Glyph::TEXT_DECORATION_OVERLINE, 11),
        );
    }
    
    /**
     * @test
     */
    public function heightOfLinePartIsLineHeightOfText()
    {
        $lineHeight = 123;
        
        $text = $this->getMockBuilder('PHPPdf\Glyph\Text')
                     ->setMethods(array('getRecurseAttribute'))
                     ->getMock();
                     
        $text->expects($this->once())
             ->method('getRecurseAttribute')
             ->with('line-height')
             ->will($this->returnValue($lineHeight));
        
        $linePart = new LinePart('', 0, 0, $text);
        
        $this->assertEquals($lineHeight, $linePart->getHeight());
    }
    
    /**
     * @test
     */
    public function addLinePartToTextOnLinePartCreation()
    {
        $text = $this->getMockBuilder('PHPPdf\Glyph\Text')
                     ->setMethods(array('addLinePart'))
                     ->getMock();
                     
        $text->expects($this->once())
             ->method('addLinePart')
             ->with($this->anything());
        
        $linePart = new LinePart('', 0, 0, $text);
    }
}