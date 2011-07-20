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
        $colorStub = Zend_Pdf_Color_Html::namedColor('black');
        $fontStub = $this->getMockBuilder('PHPPdf\Font\Font')
                         ->disableOriginalConstructor()
                         ->getMock();
        $words = 'some words';
        $startPoint = Point::getInstance(100, 120);
        $fontSize = 11;
        $documentStub = new Document();
        $xTranslationInLine = 5;
        
        $text = $this->getMockBuilder('PHPPdf\Glyph\Text')
                     ->setMethods(array('getFont', 'getAttribute', 'getGraphicsContext', 'getEncoding', 'getFontSize'))
                     ->getMock();                     
                         
        $text->expects($this->atLeastOnce())
             ->method('getFont')
             ->will($this->returnValue($fontStub));
             
        $text->expects($this->atLeastOnce())
             ->method('getFontSize')
             ->will($this->returnValue($fontSize));
        
             
        $text->expects($this->atLeastOnce())
             ->method('getAttribute')
             ->with('color')
             ->will($this->returnValue($colorStub));
             
        $text->expects($this->atLeastOnce())
             ->method('getEncoding')
             ->will($this->returnValue($encodingStub));
             
        $gc = $this->getMockBuilder('PHPPdf\Glyph\GraphicsContext')
                   ->setMethods(array('drawText', 'setFont', 'setFillColor', 'saveGS', 'restoreGS'))
                   ->disableOriginalConstructor()
                   ->getMock();
                   
        $gc->expects($this->once())
           ->method('drawText')
           ->with($words, $startPoint->getX() + $xTranslationInLine, $startPoint->getY() - $fontSize, $encodingStub);
           
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
                     ->setMethods(array('getFirstPoint'))
                     ->disableOriginalConstructor()
                     ->getMock();
        $line->expects($this->atLeastOnce())
             ->method('getFirstPoint')
             ->will($this->returnValue($startPoint));
        
        $linePart = new LinePart($words, $xTranslationInLine, $text);
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
        
        $linePart = new LinePart('', 0, $text);
        
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
        
        $linePart = new LinePart('', 0, $text);
    }
}