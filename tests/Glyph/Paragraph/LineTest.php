<?php

use PHPPdf\Glyph\Glyph;
use PHPPdf\Glyph\Paragraph;
use PHPPdf\Util\Point,
    PHPPdf\Document,
    PHPPdf\Glyph\Paragraph\Line;

class LineTest extends TestCase
{
    /**
     * @test
     */
    public function getDrawingTasksFromLineParts()
    {
        $paragraph = new Paragraph();
        $paragraph->setAttribute('text-align', Glyph::ALIGN_LEFT);
        $line = new Line($paragraph, 0, 0);
        
        $documentStub = new Document();
        
        $expectedTasks = array();
        
        for($i=0; $i<3; $i++)
        {
            $linePart = $this->getMockBuilder('PHPPdf\Glyph\Paragraph\LinePart')
                             ->setMethods(array('getDrawingTasks'))
                             ->disableOriginalConstructor()
                             ->getMock();
            
            $taskStub = 'task '.$i;
            $expectedTasks[] = $taskStub;
                             
            $linePart->expects($this->once())
                     ->method('getDrawingTasks')
                     ->with($documentStub)
                     ->will($this->returnValue(array($taskStub)));
                     
            $line->addPart($linePart);
        }
        
        $actualTasks = $line->getDrawingTasks($documentStub);
        
        $this->assertEquals($expectedTasks, $actualTasks);
    }
    
    /**
     * @test
     * @dataProvider textAlignProvider
     */
    public function applyAlignOnLinePartsWhileDrawing($paragraphFirstPoint, $width, $align, $widthOfParts, $expectedTranslation)
    {        
        $paragraph = $this->createParagraph($paragraphFirstPoint, $width, 0, 0, $align);
        
        $xTranslation = 0;
        $line = new Line($paragraph, $xTranslation, 0);
        
        foreach($widthOfParts as $widthOfPart)
        {
            $linePart = $this->getMockBuilder('PHPPdf\Glyph\Paragraph\LinePart')
                             ->setMethods(array('getDrawingTasks', 'getWidth'))
                             ->disableOriginalConstructor()
                             ->getMock();
                             
            $linePart->expects($this->once())
                     ->method('getDrawingTasks')
                     ->will($this->returnValue(array()));
            $linePart->expects($this->any())
                     ->method('getWidth')
                     ->will($this->returnValue($widthOfPart));
                     
            $line->addPart($linePart);
        }
        
        $line->getDrawingTasks(new Document());
        
        $expectedXCoord = $paragraphFirstPoint->getX() + $expectedTranslation;
        $actualXCoord = $line->getFirstPoint()->getX();
        $this->assertEquals($expectedXCoord, $actualXCoord);
    }
    
    private function createParagraph($firstPoint, $width, $paddingLeft, $paddingRight, $align)
    {
        $paragraph = $this->getMockBuilder('PHPPdf\Glyph\Paragraph')
                          ->setMethods(array('getWidth', 'getParentPaddingLeft', 'getParentPaddingRight'))
                          ->getMock();
        $paragraph->getBoundary()->setNext($firstPoint)
                                 ->setNext($firstPoint->translate($width, 0));
        $paragraph->expects($this->any())
                  ->method('getWidth')
                  ->will($this->returnValue($width));
        $paragraph->setAttribute('text-align', $align);
        
        $paragraph->expects($this->any())
                  ->method('getParentPaddingLeft')
                  ->will($this->returnValue($paddingLeft));

        $paragraph->expects($this->any())
                  ->method('getParentPaddingRight')
                  ->will($this->returnValue($paddingRight));
                  
        return $paragraph;
    }
    
    public function textAlignProvider()
    {
        return array(
            array(Point::getInstance(20, 100), 400, Glyph::ALIGN_LEFT, array(100, 100, 100), 0),
            array(Point::getInstance(20, 100), 400, Glyph::ALIGN_RIGHT, array(100, 100, 100), 100),
            array(Point::getInstance(20, 100), 400, Glyph::ALIGN_CENTER, array(100, 100, 100), 50),
        );
    }
    
    /**
     * @test
     */
    public function firstPointIsTranslatedFirstPointOfParagraph()
    {
        $yTranslation = 21;
        $xTranslation = 10;
        $paragraph = $this->getMockBuilder('PHPPdf\Glyph\Paragraph')
                          ->setMethods(array('getFirstPoint'))
                          ->getMock();
                          
        $firstPoint = Point::getInstance(100, 100);
                          
        $paragraph->expects($this->once())
                  ->method('getFirstPoint')
                  ->will($this->returnValue($firstPoint));
                  
        $line = new Line($paragraph, $xTranslation, $yTranslation);
        
        $this->assertEquals(array(110, 79), $line->getFirstPoint()->toArray());
    }
}