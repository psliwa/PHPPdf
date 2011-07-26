<?php

use PHPPdf\Util\DrawingTask;
use PHPPdf\Glyph\Paragraph\LinePart;
use PHPPdf\Util\Point;
use PHPPdf\Document;
use PHPPdf\Glyph\Text;
use PHPPdf\Glyph\Page;

class TextTest extends PHPUnit_Framework_TestCase
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
            'font-type' => Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA),
            'font-size' => 32,
        ));

        $this->document = new Document();
    }

    /**
     * @test
     */
    public function split()
    {
        $text = 'a b c d e f g h';
        $words = \explode(' ', $text);

        $glyph = new Text($text);
        $glyph->setWidth(3);
        $glyph->setHeight(96);

        $glyph->getBoundary()->setNext(0, 200)
                             ->setNext(3, 200)
                             ->setNext(3, 104)
                             ->setNext(0, 104)
                             ->close();

        $lineSplit = 30;
        $result = $glyph->split($lineSplit);

        $this->assertEquals(30, $glyph->getHeight());
        $this->assertEquals(170, $glyph->getDiagonalPoint()->getY());

        $this->assertEquals(66, $result->getHeight());
        $this->assertEquals(170, $result->getFirstPoint()->getY());
    }

    /**
     * @test
     */
    public function mergingTextByAddingChildren()
    {
        $anotherText = 'inny tekst';
        $text = new Text($anotherText);

        $oldText = $this->text->getText();

        $this->text->add($text);

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
        
        $transformator = $this->getMock('PHPPdf\Glyph\TextTransformator', array('transform'));
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
        $documentStub = new Document();
        
        $expectedTasks = array();
        
        for($i=0; $i<3; $i++)
        {
            $linePart = $this->getMockBuilder('PHPPdf\Glyph\Paragraph\LinePart')
                             ->setMethods(array('getDrawingTasks'))
                             ->disableOriginalConstructor()
                             ->getMock();
            
            $taskStub = new DrawingTask(function(){});
            $expectedTasks[] = $taskStub;
                             
            $linePart->expects($this->once())
                     ->method('getDrawingTasks')
                     ->with($documentStub)
                     ->will($this->returnValue(array($taskStub)));
                     
            $this->text->addLinePart($linePart);
        }
        
        $actualTasks = $this->text->getDrawingTasks($documentStub);
        
        $this->assertEquals($expectedTasks, $actualTasks);
    }
}