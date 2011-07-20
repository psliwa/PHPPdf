<?php

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
        $line = new Line(new Paragraph(), 0, 0);
        
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
                     
            $line->addLine($linePart);
        }
        
        $actualTasks = $line->getDrawingTasks($documentStub);
        
        $this->assertEquals($expectedTasks, $actualTasks);
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