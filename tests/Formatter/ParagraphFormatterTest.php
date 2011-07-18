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
    public function calculateTextsPositions($x, $width, $height, $align, array $fontSizes, array $wordsSizes, array $expectedPositions)
    {
        $paragraph = $this->createParagraph($x, $height, $width, $height, $align);
        $this->createTextGlyphAndAddToParagraph($wordsSizes, $fontSizes, $paragraph);
        
        $this->formatter->format($paragraph, $this->document);
        
        foreach($paragraph->getChildren() as $i => $textGlyph)
        {
            $this->assertPointEquals($expectedPositions[$i][0], $textGlyph->getFirstPoint());
            $this->assertPointEquals($expectedPositions[$i][1], $textGlyph->getDiagonalPoint());
        }
    }
    
    private function assertPointEquals($expectedPoint, $actualPoint)
    {
        $this->assertEquals($expectedPoint[0], $actualPoint[0], '', 1);
        $this->assertEquals($expectedPoint[1], $actualPoint[1], '', 1);
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
                Glyph::ALIGN_LEFT,
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
                2,
                25, 
                200,
                Glyph::ALIGN_RIGHT,
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
                        array(5, 200),
                        array(27, 200 - $lineHeightFor15),
                    ),
                    array(
                        array(5, 200 - $lineHeightFor15),
                        array(27, 200 - ($lineHeightFor15 + 2*$lineHeightFor12)),
                    ),
                ),
            ),
        );
    }
    
    private function createParagraph($x, $y, $width, $height, $align)
    {
        $parent = new Container();
        $parent->setWidth($width);
        $paragraph = new Paragraph();
        $paragraph->setAttribute('text-align', $align);
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