<?php

use PHPPdf\Document;
use PHPPdf\Util\Point;
use PHPPdf\Glyph\Paragraph\LinePart;

class LinePartTest extends TestCase
{
    /**
     * @test
     */
    public function drawLinePartUsingTextGlyphAttributes()
    {
        $encodingStub = 'utf-16';
        $colorStub = $this->getMockBuilder('PHPPdf\Engine\Color')
                          ->getMock();
        $fontStub = $this->getMockBuilder('PHPPdf\Engine\Font')
                         ->disableOriginalConstructor()
                         ->getMock();
        $words = 'some words';
        $startPoint = Point::getInstance(100, 120);
        $fontSize = 11;
        $documentStub = new Document();
        $xTranslationInLine = 5;
        
        $lineHeightOfText = 15;
        $heightOfLine = 18;        
        
        $text = $this->getMockBuilder('PHPPdf\Glyph\Text')
                     ->setMethods(array('getFont', 'getAttribute', 'getRecurseAttribute', 'getGraphicsContext', 'getEncoding', 'getFontSize'))
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
             
        $gc = $this->getMockBuilder('PHPPdf\Engine\GraphicsContext')
        		   ->getMock();
                   
        $gc->expects($this->once())
           ->method('drawText')
           ->with($words, $startPoint->getX() + $xTranslationInLine, $startPoint->getY() - $fontSize - ($heightOfLine - $lineHeightOfText), $encodingStub);
           
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
        
        $linePartWidth = 100;
        $linePart = new LinePart($words, $linePartWidth, $xTranslationInLine, $text);
        $linePart->setLine($line);
        
        $tasks = $linePart->getDrawingTasks($documentStub);
        
        foreach($tasks as $task)
        {
            $task->invoke();
        }
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