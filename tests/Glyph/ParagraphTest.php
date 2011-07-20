<?php

use PHPPdf\Document;
use PHPPdf\Glyph\Text;
use PHPPdf\Glyph\Paragraph;

class ParagraphTest extends TestCase
{
    private $paragraph;
    
    public function setUp()
    {
        $this->paragraph = new Paragraph();
    }
    
    /**
     * @test
     * @dataProvider textProvider
     */
    public function trimTextElementsThatTextElementsAreSeparatedAtMostByOneSpace(array $texts)
    {
        foreach($texts as $text)
        {
            $this->paragraph->add(new Text($text));
        }
        
        $isPreviousTextEndsWithWhiteChars = false;
        foreach($this->paragraph->getChildren() as $textGlyph)
        {
            $isStartsWithWhiteChars = ltrim($textGlyph->getText()) != $textGlyph->getText();
            
            $this->assertFalse($isStartsWithWhiteChars && $isPreviousTextEndsWithWhiteChars);
            
            $isPreviousTextEndsWithWhiteChars = rtrim($textGlyph->getText()) != $textGlyph->getText();
        }
        
        $firstText = $this->paragraph->getChild(0);
        
        $this->assertTrue($firstText->getText() == ltrim($firstText->getText()), 'first text element isnt left trimmed');
    }
    
    public function textProvider()
    {
        return array(
            array(
                array('some text ', ' some another text'),
            ),
            array(
                array('   some text ', '    some another text    ', '    some another text'),
            ),
        );
    }
    
    /**
     * @test
     */
    public function getDrawingTasksFromLineObjects()
    {
        $documentStub = new Document();
        
        $expectedTasks = array();
        
        for($i=0; $i<3; $i++)
        {
            $line = $this->getMockBuilder('PHPPdf\Glyph\Paragraph\Line')
                         ->setMethods(array('getDrawingTasks'))
                         ->disableOriginalConstructor()
                         ->getMock();
            
            $taskStub = 'task '.$i;
            $expectedTasks[] = $taskStub;
                             
            $line->expects($this->once())
                 ->method('getDrawingTasks')
                 ->with($documentStub)
                 ->will($this->returnValue(array($taskStub)));
                     
            $this->paragraph->addLine($line);
        }
        
        $actualTasks = $this->paragraph->getDrawingTasks($documentStub);
        
        $this->assertEquals($expectedTasks, $actualTasks);
    }
}