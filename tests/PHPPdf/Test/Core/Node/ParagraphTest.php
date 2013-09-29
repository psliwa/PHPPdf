<?php

namespace PHPPdf\Test\Core\Node;

use PHPPdf\Core\Node\Page;

use PHPPdf\Core\Node\Container;

use PHPPdf\Core\Node\Node;

use PHPPdf\Core\DrawingTask;

use PHPPdf\Core\DrawingTaskHeap;

use PHPPdf\Core\Node\Paragraph\LinePart;
use PHPPdf\Core\Node\Paragraph\Line;
use PHPPdf\Core\Point;
use PHPPdf\Core\Document;
use PHPPdf\Core\Node\Text;
use PHPPdf\Core\Node\Paragraph;

class ParagraphTest extends \PHPPdf\PHPUnit\Framework\TestCase
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
        foreach($this->paragraph->getChildren() as $textNode)
        {
            $isStartsWithWhiteChars = ltrim($textNode->getText()) != $textNode->getText();
            
            $this->assertFalse($isStartsWithWhiteChars && $isPreviousTextEndsWithWhiteChars);
            
            $isPreviousTextEndsWithWhiteChars = rtrim($textNode->getText()) != $textNode->getText();
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
    public function dontTrimAllWhiteSpacesFromTheMiddleText()
    {
        $firstText = new Text('abc');
        $emptyText = new Text('    ');
        $lastText = new Text('abc');
        
        foreach(array($firstText, $emptyText, $lastText) as $text)
        {
            $this->paragraph->add($text);
        }
        
        $this->assertEquals(' ', $emptyText->getText());
    }
    
    /**
     * @test
     */
    public function formatLinesWhileGettingTasks()
    {
        $documentStub = $this->createDocumentStub();
        
        $expectedTasks = array();
        
        for($i=0; $i<3; $i++)
        {
            $line = $this->getMockBuilder('PHPPdf\Core\Node\Paragraph\Line')
                         ->setMethods(array('format'))
                         ->disableOriginalConstructor()
                         ->getMock();
                             
            $line->expects($this->once())
                 ->method('format');
                     
            $this->paragraph->addLine($line);
        }
        
        $tasks = new DrawingTaskHeap();
        $this->paragraph->collectOrderedDrawingTasks($documentStub, $tasks);
    }
    
    /**
     * @test
     */
    public function getDrawingTasksFromTextObjects()
    {
        $documentStub = $this->createDocumentStub();
        
        $expectedTasks = array();
        
        $tasks = new DrawingTaskHeap();
        
        for($i=0; $i<3; $i++)
        {
            $text = $this->getMockBuilder('PHPPdf\Core\Node\Text')
                         ->setMethods(array('collectOrderedDrawingTasks'))
                         ->disableOriginalConstructor()
                         ->getMock();
            
            $taskStub = new DrawingTask(function(){});
            $expectedTasks[] = $taskStub;
                             
            $text->expects($this->once())
                 ->method('collectOrderedDrawingTasks')
                 ->with($documentStub, $this->isInstanceOf('PHPPdf\Core\DrawingTaskHeap'))
                 ->will($this->returnCallback(function() use($tasks, $taskStub){
                     $tasks->insert($taskStub);
                 }));
                     
            $this->paragraph->add($text);
        }
        
        $this->paragraph->collectOrderedDrawingTasks($documentStub, $tasks);
        
        $this->assertEquals(count($expectedTasks), count($tasks));
    }
    
    /**
     * @test
     * @dataProvider propagateParagraphWidthToParentExceptPageProvider
     */
    public function propagateParagraphWidthToParentExceptPage($isParentPage, $parentWidth, $paragraphWidth, $expectsParentWidth)
    {
        $parent = $isParentPage ? new Page() : new Container();
        $parent->setWidth($parentWidth);
        
        $this->paragraph->setParent($parent);
        $this->paragraph->setWidth($paragraphWidth);
        
        $this->assertEquals($expectsParentWidth, $parent->getWidth());
    }
    
    public function propagateParagraphWidthToParentExceptPageProvider()
    {
        return array(
            array(false, 100, 200, 200),
            array(true, 100, 200, 100),
        );
    }
    
    /**
     * _____________________________
     * |                            |
     * |              ______________|
     * |_____________|              | <- breaking here
     * |                            |
     * |____________________________|
     * 
     * @test
     */
    public function breaking()
    {
        $x = 0;
        $y = 500;
        $width = 500;
        $height = 500;
        
        $firstPoint = Point::getInstance($x, $y);
        
        $paragraphParent = new Container();
        $paragraphParent->setWidth($width);
        $paragraphParent->setHeight($height);

        $paragraph = new Paragraph();
        $paragraph->setParent($paragraphParent);
        
        $text1 = new Text();
        $text1->getBoundary()->setNext($firstPoint)
                             ->setNext($firstPoint->translate($width, 0))
                             ->setNext($firstPoint->translate($width, 200))
                             ->setNext($firstPoint->translate($width/2, 200))
                             ->setNext($firstPoint->translate($width/2, 250))
                             ->setNext($firstPoint->translate(0, 250))
                             ->close();
                             
        $text2 = new Text();
        $text2->getBoundary()->setNext($firstPoint->translate($width/2, 200))
                             ->setNext($firstPoint->translate($width, 200))
                             ->setNext($firstPoint->translate($width, 500))
                             ->setNext($firstPoint->translate(0, 500))
                             ->setNext($firstPoint->translate(0, 250))
                             ->setNext($firstPoint->translate($width/2, 250))
                             ->close();
        $text1->setWidth(500);
        $text1->setHeight(250);
        $text2->setWidth(500);
        $text2->setHeight(300);
        $text1->setAttribute('line-height', 100);
        $text2->setAttribute('line-height', 100);
        
        for($i=0; $i<2; $i++)
        {
            $line = new Line($paragraph, 0, $i*100);
            $part = new LinePart('', 500, 0, $text1);
            $line->addPart($part);
            
            $paragraph->addLine($line);
        }
        
        $line = new Line($paragraph, 0, 200);
        $line->addPart(new LinePart('', $width/2, 0, $text1));
        $line->addPart(new LinePart('', $width/2, 250, $text2));
        $paragraph->addLine($line);
        
        for($i=0; $i<2; $i++)
        {
            $line = new Line($paragraph, 0, ($i+3)*100);
            $part = new LinePart('', 500, 0, $text2);
            $line->addPart($part);
            $paragraph->addLine($line);
        }
        
        $paragraph->add($text1);
        $paragraph->add($text2);
        
        $paragraph->getBoundary()->setNext($firstPoint)
                                 ->setNext($firstPoint->translate($width, 0))
                                 ->setNext($firstPoint->translate($width, 500))
                                 ->setNext($firstPoint->translate(0, 500))
                                 ->close();
        $paragraph->setHeight(500);
        
        $paragraphProduct = $paragraph->breakAt(225);
        
        $this->assertEquals(200, $paragraph->getHeight());
        $this->assertEquals(200, $paragraph->getFirstPoint()->getY() - $paragraph->getDiagonalPoint()->getY());

        $this->assertEquals(300, $paragraphProduct->getHeight());
        $this->assertEquals(300, $paragraphProduct->getFirstPoint()->getY() - $paragraphProduct->getDiagonalPoint()->getY());
        
        $this->assertTrue($paragraphProduct !== null);
        
        $this->assertEquals(200, $paragraph->getHeight());
        
        $this->assertEquals(2, count($paragraph->getLines()));
        $this->assertEquals(3, count($paragraphProduct->getLines()));
        
        $this->assertEquals(1, count($paragraph->getChildren()));
        $this->assertEquals(2, count($paragraphProduct->getChildren()));
        
        foreach($paragraphProduct->getLines() as $i => $line)
        {
            $this->assertEquals($i*100, $line->getYTranslation());
            foreach($line->getParts() as $part)
            {
//                $this->assertTrue($part->getText() !== $text1);
//                $this->assertTrue($part->getText() !== $text2);
            }
        }
    }
    
    /**
     * @test
     * @dataProvider linesWidthsProvider
     */
    public function minWidthIsMaxOfLineMinWidth(array $linesWidths)
    {
        foreach($linesWidths as $width)
        {
            $line = $this->getMockBuilder('PHPPdf\Core\Node\Paragraph\Line')
                         ->setMethods(array('getTotalWidth'))
                         ->disableOriginalConstructor()
                         ->getMock();
            $line->expects($this->atLeastOnce())
                 ->method('getTotalWidth')
                 ->will($this->returnValue($width));
                 
            $this->paragraph->addLine($line);
        }
        
        $expectedMinWidth = max($linesWidths);
        
        $this->assertEquals($expectedMinWidth, $this->paragraph->getMinWidth());
    }
    
    public function linesWidthsProvider()
    {
        return array(
            array(array(
                10, 20 ,30            
            )),
            array(array(
                20, 20 ,10            
            )),
        );
    }
}