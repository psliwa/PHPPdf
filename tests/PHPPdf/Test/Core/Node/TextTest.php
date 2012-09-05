<?php

namespace PHPPdf\Test\Core\Node;

use PHPPdf\Core\DrawingTaskHeap;

use PHPPdf\Core\DrawingTask;
use PHPPdf\Core\Node\Paragraph\LinePart;
use PHPPdf\Core\Point;
use PHPPdf\Core\Document;
use PHPPdf\Core\Node\Text;
use PHPPdf\Core\Node\Page;

class TextTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $text;
    private $document;
    private $page;

    const PAGE_WIDTH = 700;
    const PAGE_HEIGHT = 700;

    public function setUp()
    {
        $this->text = new Text('some text');
        $this->page = new Page(array(
            'page-size' => self::PAGE_WIDTH.':'.self::PAGE_HEIGHT,
            'font-type' => 'helvetica',
            'font-size' => 32,
        ));

        $this->document = $this->createDocumentStub();
    }

    /**
     * @test
     */
    public function breakAt()
    {
        $text = 'a b c d e f g h';
        $words = \explode(' ', $text);

        $node = new Text($text);
        $node->setWidth(3);
        $node->setHeight(96);

        $node->getBoundary()->setNext(0, 200)
                             ->setNext(3, 200)
                             ->setNext(3, 104)
                             ->setNext(0, 104)
                             ->close();

        $breakingLine = 30;
        $result = $node->breakAt($breakingLine);

        $this->assertEquals(30, $node->getHeight());
        $this->assertEquals(170, $node->getDiagonalPoint()->getY());

        $this->assertEquals(66, $result->getHeight());
        $this->assertEquals(170, $result->getFirstPoint()->getY());
    }

    /**
     * @test
     */
    public function mergeChildTextsAfterPreFormat()
    {
        $anotherText = 'inny tekst';
        $text = new Text($anotherText);

        $oldText = $this->text->getText();

        $this->text->add($text);        
        $this->text->preFormat($this->document);

        $this->assertEquals($oldText.$anotherText, $this->text->getText());
    }

    /**
     * @test
     * @dataProvider lineSizesProvider
     */
    public function minimumWidthIsTheWidestLinePart(array $lineSizes)
    {
        foreach($lineSizes as $width)
        {
            $linePart = new LinePart('', $width, 0, $this->text);
        }

        $this->assertEquals(max($lineSizes), $this->text->getMinWidth());
    }

    public function lineSizesProvider()
    {
        return array(
            array(
                array(120, 100, 130),
                array(1, 2, 0),
            ),
        );
    }
    
    /**
     * @test
     */    
    public function useTextTransformatorToSettingText()
    {
        $textStub = 'some text';
        
        $transformator = $this->getMock('PHPPdf\Core\Node\TextTransformator', array('transform'));
        $transformator->expects($this->once())
                      ->method('transform')
                      ->will($this->returnValue($textStub));
        
        $this->text->setTextTransformator($transformator);
        
        $this->text->setText('ac');
        
        $this->assertEquals($textStub, $this->text->getText());
    }
    
    /**
     * @test
     * @dataProvider wordsSizesProvider
     */
    public function setWordsSizes(array $words, array $sizes, $expectedException)
    {
        try
        {
            $this->text->setWordsSizes($words, $sizes);
            
            if($expectedException)
            {
                $this->fail('expected exception');
            }
            
            $this->assertEquals($words, $this->text->getWords());
            $this->assertEquals($sizes, $this->text->getWordsSizes());
        }
        catch(\InvalidArgumentException $e)
        {
            if(!$expectedException)
            {
                $this->fail('unexpected exception');
            }
        }
    }
    
    public function wordsSizesProvider()
    {
        return array(
            array(
                array('some', 'another'),
                array(100, 120),
                false
            ),
            array(
                array('some'),
                array(100, 120),
                true
            ),
        );
    }
    
    /**
     * @test
     */
    public function startPointOfEachLineShouldBeMovedWhileTranlateing()
    {
        $x = 10;
        $y = 15;
        $transX = 3;
        $transY = 5;
        
        $this->text->addLineOfWords(array('word'), 10, Point::getInstance($x, $y));
        
        $this->text->translate($transX, $transY);
        
        list($point) = $this->text->getPointsOfWordsLines();
        $this->assertEquals(array($x+$transX, $y - $transY), $point->toArray());
    }
    
    /**
     * @test
     */
    public function getDrawingTasksFromLineParts()
    {       
        $documentStub = $this->createDocumentStub();
        
        $tasks = new DrawingTaskHeap();
        
        for($i=0; $i<3; $i++)
        {
            $linePart = $this->getMockBuilder('PHPPdf\Core\Node\Paragraph\LinePart')
                             ->setMethods(array('collectOrderedDrawingTasks'))
                             ->disableOriginalConstructor()
                             ->getMock();
                             
            $linePart->expects($this->once())
                     ->method('collectOrderedDrawingTasks')
                     ->with($documentStub, $tasks);
                     
            $this->text->addLinePart($linePart);
        }
        
        $this->text->collectOrderedDrawingTasks($documentStub, $tasks);
    }
    
    /**
     * @test
     */
    public function removeLinePart()
    {
        $text = new Text();
        
        $linePart1 = $this->getMockBuilder('PHPPdf\Core\Node\Paragraph\LinePart')
                          ->disableOriginalConstructor()
                          ->getMock();
        $linePart2 = $this->getMockBuilder('PHPPdf\Core\Node\Paragraph\LinePart')
                          ->disableOriginalConstructor()
                          ->getMock();
                          
        $text->addLinePart($linePart1);
        $text->addLinePart($linePart2);
        
        $this->assertEquals(2, count($text->getLineParts()));
        
        $text->removeLinePart($linePart1);
        
        $this->assertEquals(1, count($text->getLineParts()));
        $text->removeLinePart($linePart1);
        $this->assertEquals(1, count($text->getLineParts()));
    }
}