<?php

namespace PHPPdf\Test\Core\Node\Paragraph;

use PHPPdf\Core\DrawingTaskHeap;

use PHPPdf\Core\Node\Text;
use PHPPdf\Core\Node\Node;
use PHPPdf\Core\Document;
use PHPPdf\Core\Point;
use PHPPdf\Core\Node\Paragraph\LinePart;

class LinePartTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    /**
     * @test
     * @dataProvider drawingDataProvider
     */
    public function drawLinePartUsingTextNodeAttributes($fontSize, $lineHeightOfText, $textDecoration, $expectedLineDecorationYCoord, $wordSpacing)
    {
        $encodingStub = 'utf-16';
        $colorStub = '#654321';
        $fontStub = $this->getMockBuilder('PHPPdf\Core\Engine\Font')
                         ->disableOriginalConstructor()
                         ->getMock();
        $words = 'some words';
        $startPoint = Point::getInstance(100, 120);

        $documentStub = $this->createDocumentStub();
        $xTranslationInLine = 5;
        $linePartWidth = 100;
        $alpha = 0.5;
        
        $heightOfLine = 18;        
        
        $text = $this->getMockBuilder('PHPPdf\Core\Node\Text')
                     ->setMethods(array('getFont', 'getAttribute', 'getRecurseAttribute', 'getGraphicsContext', 'getEncoding', 'getFontSize', 'getTextDecorationRecursively', 'getAlpha', 'getAncestorWithRotation'))
                     ->getMock();
                         
        $text->expects($this->atLeastOnce())
             ->method('getFont')
             ->with($documentStub)
             ->will($this->returnValue($fontStub));
             
        $text->expects($this->atLeastOnce())
             ->method('getAlpha')
             ->will($this->returnValue($alpha));
             
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
        $text->expects($this->atLeastOnce())
             ->method('getAncestorWithRotation')
             ->will($this->returnValue(null));
             
        $gc = $this->getMockBuilder('PHPPdf\Core\Engine\GraphicsContext')
        		   ->getMock();

	    $expectedXCoord = $startPoint->getX() + $xTranslationInLine;
	    $expectedYCoord = $startPoint->getY() - $fontSize - ($heightOfLine - $lineHeightOfText);
	    
	    $expectedWordSpacing = 0;
	    if($wordSpacing !== null)
	    {
            $expectedWordSpacing = $wordSpacing;
	    }

        $gc->expects($this->once())
           ->method('drawText')
           ->with($words, $expectedXCoord, $expectedYCoord, $encodingStub, $expectedWordSpacing);
	    
           
        $gc->expects($this->once())
           ->method('setFont')
           ->with($fontStub, $fontSize);
           
        $gc->expects($this->once())
           ->method('setFillColor')
           ->with($colorStub);
        $gc->expects($this->once())
           ->method('setAlpha')
           ->with($alpha);
           
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
             
        $line = $this->getMockBuilder('PHPPdf\Core\Node\Paragraph\Line')
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
        $linePart->setWordSpacing($wordSpacing);
        $linePart->setLine($line);
        
        $tasks = new DrawingTaskHeap();
        $linePart->collectOrderedDrawingTasks($documentStub, $tasks);
        
        foreach($tasks as $task)
        {
            $task->invoke();
        }
    }
    
    public function drawingDataProvider()
    {
        return array(
            array(11, 15, Node::TEXT_DECORATION_NONE, false, null),
            array(11, 15, Node::TEXT_DECORATION_UNDERLINE, -1, null),
            array(18, 15, Node::TEXT_DECORATION_LINE_THROUGH, 6, null),
            array(12, 15, Node::TEXT_DECORATION_OVERLINE, 11, 13),
        );
    }
    
    /**
     * @test
     */
    public function heightOfLinePartIsLineHeightOfText()
    {
        $lineHeight = 123;
        
        $text = $this->getMockBuilder('PHPPdf\Core\Node\Text')
                     ->setMethods(array('getLineHeightRecursively'))
                     ->getMock();
                     
        $text->expects($this->once())
             ->method('getLineHeightRecursively')
             ->will($this->returnValue($lineHeight));
        
        $linePart = new LinePart('', 0, 0, $text);
        
        $this->assertEquals($lineHeight, $linePart->getHeight());
    }
    
    /**
     * @test
     */
    public function addLinePartToTextOnLinePartCreation()
    {
        $text = $this->getMockBuilder('PHPPdf\Core\Node\Text')
                     ->setMethods(array('addLinePart', 'removeLinePart'))
                     ->getMock();
                     
        $text->expects($this->at(0))
             ->method('addLinePart')
             ->with($this->isInstanceOf('PHPPdf\Core\Node\Paragraph\LinePart'));
             
        $text->expects($this->at(1))
             ->method('removeLinePart')
             ->with($this->isInstanceOf('PHPPdf\Core\Node\Paragraph\LinePart'));

        $linePart = new LinePart('', 0, 0, $text);

        $newText = $this->getMockBuilder('PHPPdf\Core\Node\Text')
                        ->setMethods(array('addLinePart'))
                        ->getMock();
        $newText->expects($this->at(0))
                ->method('addLinePart')
                ->with($this->isInstanceOf('PHPPdf\Core\Node\Paragraph\LinePart'));
                
        $linePart->setText($newText);
    }
    
    /**
     * @test
     */
    public function getNumberOfWords()
    {
        $words = 'some words';
        $linePart = new LinePart($words, 0, 0, new Text());
        
        $this->assertEquals(2, $linePart->getNumberOfWords());
        
        $linePart->setWords('some more words');
        $this->assertEquals(3, $linePart->getNumberOfWords());
    }
    
    /**
     * @test
     */
    public function wordSpacingHasAnImpactOnWidth()
    {
        $words = 'some more words';
        $width = 100;
        $linePart = new LinePart($words, $width, 0, new Text());
        
        $wordSpacing = 5;
        $linePart->setWordSpacing($wordSpacing);
        
        $expectedWidth = $width + ($linePart->getNumberOfWords()-1)*5;
        $this->assertEquals($expectedWidth, $linePart->getWidth());
    }
}