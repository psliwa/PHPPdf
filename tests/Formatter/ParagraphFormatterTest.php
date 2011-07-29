<?php

use PHPPdf\Glyph\Glyph;
use PHPPdf\Glyph\Container;
use PHPPdf\Document;
use PHPPdf\Util\Point;
use PHPPdf\Glyph\Text;
use PHPPdf\Glyph\Paragraph;
use PHPPdf\Formatter\ParagraphFormatter;

class ParagraphFormatterTest extends TestCase
{
    private $objectMother;
    
    private $formatter;
    private $document;
    
    protected function init()
    {
        $this->objectMother = new GenericGlyphObjectMother($this);
    }

    public function setUp()
    {
        $this->formatter = new ParagraphFormatter();
        $this->document = new Document();
    }
    
    /**
     * @test
     * @dataProvider dataProvider
     */
    public function calculateTextsPositions($x, $width, $height, array $fontSizes, array $wordsSizes, array $expectedPositions)
    {
        $paragraph = $this->createParagraph($x, $height, $width, $height);
        $this->createTextGlyphAndAddToParagraph($wordsSizes, $fontSizes, $paragraph);
        
        $this->formatter->format($paragraph, $this->document);
        
        foreach($paragraph->getChildren() as $i => $textGlyph)
        {
            $this->assertPointEquals($expectedPositions[$i][0], $textGlyph->getFirstPoint(), sprintf('%%sfirst point of "%d" text is invalid', $i));
            $this->assertPointEquals($expectedPositions[$i][1], $textGlyph->getDiagonalPoint(), sprintf('%%sdiagonal point of "%d" text is invalid', $i));
        }
    }
    
    private function assertPointEquals($expectedPoint, $actualPoint, $message = '')
    {
        $this->assertEquals($expectedPoint[0], $actualPoint[0], sprintf($message, 'coord x of '), 1);
        $this->assertEquals($expectedPoint[1], $actualPoint[1], sprintf($message, 'coord y of '), 1);
    }
        
    public function dataProvider()
    {
        $lineHeightFor15 = $this->getLineHeight(15);
        $lineHeightFor12 = $this->getLineHeight(12);
        
        return array(
            array(
                2,
                25, 
                200,
                array(15, 12),
                array(
                    array(
                        array('some', 'another'),
                        array(10, 12),
                    ),                    
                    array(
                        array('some', 'another', 'anotherYet'),
                        array(10, 12, 15),
                    ),                    
                ),
                array(
                    array(
                        array(2, 200),
                        array(24, 200 - $lineHeightFor15),
                    ),
                    array(
                        array(2, 200 - $lineHeightFor15),
                        array(17, 200 - ($lineHeightFor15 + 2*$lineHeightFor12)),
                    ),
                ),
            ),
            array(
                0,
                25,
                200,
                array(15, 12),
                array(
                    array(
                        array('word'),
                        array(10),
                    ),
                    array(
                        array('some'),
                        array(10),
                    )
                ),
                array(
                    array(
                        array(0, 200),
                        array(10, 200 - $lineHeightFor15),
                    ),
                    array(
                        array(10, 200 - ($lineHeightFor15 - $lineHeightFor12)),
                        array(20, 200 - ($lineHeightFor15 - $lineHeightFor12) - $lineHeightFor12),
                    ),
                ),
            ),
            array(
                0,
                25,
                200,
                array(12, 15),
                array(
                    array(
                        array('word'),
                        array(10),
                    ),
                    array(
                        array('some'),
                        array(10),
                    )
                ),
                array(
                    array(
                        array(0, 200 - ($lineHeightFor15 - $lineHeightFor12)),
                        array(10, 200 - ($lineHeightFor15 - $lineHeightFor12) - $lineHeightFor12),
                    ),
                    array(
                        array(10, 200),
                        array(20, 200 - $lineHeightFor15),
                    ),
                ),
            ),
        );
    }
    
    private function createParagraph($x, $y, $width, $height)
    {
        $parent = new Container();
        $parent->setWidth($width);
        $paragraph = new Paragraph();
        $parent->add($paragraph);
        
        $boundary = $this->objectMother->getBoundaryStub($x, $y, $width, $height);
        $this->invokeMethod($paragraph, 'setBoundary', array($boundary));
        
        return $paragraph;
    }
    
    private function createTextGlyphAndAddToParagraph(array $wordsSizes, array $fontSizes, Paragraph $paragraph)
    {
        foreach($wordsSizes as $index => $wordsSizesForGlyph)
        {
            $textGlyph = new Text();
            
            list($words, $sizes) = $wordsSizesForGlyph;
            $textGlyph->setWordsSizes($words, $sizes);
            $textGlyph->setFontSize($fontSizes[$index]);
            
            $paragraph->add($textGlyph);
        }
    }
    
    private function getLineHeight($fontSize)
    {
        return $fontSize*1.2;
    }
}