<?php

namespace PHPPdf\Test\Core\Node\Paragraph;

use PHPPdf\Core\DrawingTaskHeap;

use PHPPdf\Core\Node\Paragraph\Line;
use PHPPdf\Core\Node\Paragraph;
use PHPPdf\Core\Node\Text;
use PHPPdf\Core\Node\Node;
use PHPPdf\Core\Document;
use PHPPdf\Core\Point;
use PHPPdf\Core\Node\Paragraph\LinePart;

class LinePartTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    const ENCODING = 'utf-16';
    const COLOR = '#654321';
    const WORDS = 'some words';
    const X_TRANSLATION = 5;
    const WIDTH = 100;
    const ALPHA = 0.5;
    const LINE_HEIGHT = 18;

    /**
     * @test
     * @dataProvider drawingDataProvider
     */
    public function drawLinePartUsingTextNodeAttributes($fontSize, $lineHeightOfText, $textDecoration, $expectedLineDecorationYCoord, $wordSpacing)
    {
        $fontStub = $this->createFontStub();
        $startPoint = Point::getInstance(100, 120);

        $documentStub = $this->createDocumentStub();

        $linePartWidthGross = self::WIDTH +  ($this->getWordsCount() - 1)*$wordSpacing;

	    $expectedXCoord = $startPoint->getX() + self::X_TRANSLATION;
	    $expectedYCoord = $startPoint->getY() - $fontSize - (self::LINE_HEIGHT - $lineHeightOfText);
	    
	    $expectedWordSpacing = $wordSpacing !== null ? $wordSpacing : 0;

        $gc = $this->getMock('PHPPdf\Core\Engine\GraphicsContext');

        $this->expectDrawText($gc, Point::getInstance($expectedXCoord, $expectedYCoord), $fontStub, $fontSize, $expectedWordSpacing);
           
        if($expectedLineDecorationYCoord === false)
        {
            $gc->expects($this->never())
               ->method('drawLine');
        }
        else
        {
            $expectedYCoord = $expectedYCoord + $expectedLineDecorationYCoord;
            $this->expectDrawLine($gc, self::COLOR,
                Point::getInstance($expectedXCoord, $expectedYCoord),
                Point::getInstance($expectedXCoord + $linePartWidthGross, $expectedYCoord)
            );
        }

        $text = $this->createTextStub($fontStub, $gc, array(
            'alpha' => self::ALPHA,
            'font-size' => $fontSize,
            'color' => self::COLOR,
            'line-height' => $lineHeightOfText,
            'text-decoration' => $textDecoration,
        ));

        $line = $this->createLineStub($startPoint, self::LINE_HEIGHT);
        
        $linePart = new LinePart(self::WORDS, self::WIDTH, self::X_TRANSLATION, $text);
        $linePart->setWordSpacing($wordSpacing);
        $linePart->setLine($line);
        
        $tasks = new DrawingTaskHeap();
        $linePart->collectOrderedDrawingTasks($documentStub, $tasks);
        
        foreach($tasks as $task)
        {
            $task->invoke();
        }
    }

    private function createTextStub($font, $gc, array $attributes)
    {
        $text = new LinePartTest_Text('', $attributes);
        $text->setFont($font);
        $text->setEncoding(self::ENCODING);
        $text->setGraphicsContext($gc);

        return $text;
    }

    private function createLineStub(Point $firstPoint, $height)
    {
        return new LinePartTest_Line($firstPoint, $height);
    }

    private function getWordsCount()
    {
        return count(explode(' ', self::WORDS));
    }

    private function expectDrawText($gc, Point $startPoint, $font, $fontSize, $wordSpacing)
    {

        $gc->expects($this->once())
            ->method('drawText')
            ->with(self::WORDS, $startPoint->getX(), $startPoint->getY(), self::ENCODING, $wordSpacing);


        $gc->expects($this->once())
            ->method('setFont')
            ->with($font, $fontSize);

        $gc->expects($this->once())
            ->method('setFillColor')
            ->with(self::COLOR);
        $gc->expects($this->once())
            ->method('setAlpha')
            ->with(self::ALPHA);

        $gc->expects($this->once())
            ->method('saveGs');
        $gc->expects($this->once())
            ->method('restoreGS');
    }

    private function expectDrawLine($gc, $color, Point $startPoint, Point $endPoint)
    {
        $gc->expects($this->once())
            ->method('setLineColor')
            ->id('color')
            ->with($color);

        $gc->expects($this->once())
            ->method('setLineWidth')
            ->id('line')
            ->with(0.5);

        $gc->expects($this->once())
            ->after('color')
            ->method('drawLine')
            ->with($startPoint->getX(), $startPoint->getY(), $endPoint->getX(), $endPoint->getY());
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
        
        $expectedWidth = $width + ($linePart->getNumberOfWords()-1)*$wordSpacing;
        $this->assertEquals($expectedWidth, $linePart->getWidth());
    }

    /**
     * @return mixed
     */
    private function createFontStub()
    {
        return $this->getMockBuilder('PHPPdf\Core\Engine\Font')
            ->disableOriginalConstructor()
            ->getMock();
    }
}

class LinePartTest_Line extends Line
{
    private $height;

    public function __construct(Point $firstPoint, $height)
    {
        $paragraph = new Paragraph();
        $paragraph->getBoundary()->setNext($firstPoint);
        parent::__construct($paragraph, 0, 0);
        $this->height = $height;
    }

    public function getHeight()
    {
        return $this->height;
    }
}

class LinePartTest_Text extends Text
{
    private $font;
    private $encoding;
    private $graphicsContext;

    public function setFont($font)
    {
        $this->font = $font;
    }

    public function getFont(Document $document)
    {
        return $this->font;
    }

    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
    }

    public function getEncoding()
    {
        return $this->encoding;
    }

    public function setGraphicsContext($graphicsContext)
    {
        $this->graphicsContext = $graphicsContext;
    }

    public function getGraphicsContext()
    {
        return $this->graphicsContext;
    }
}