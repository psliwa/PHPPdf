<?php

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
    public function calculateTextsPositions($width, $height, array $fontSizes, array $wordsSizes, array $expectedPositions)
    {
        $parent = new Container();
        $parent->setWidth($width);
        $paragraph = new Paragraph();
        $parent->add($paragraph);
        
        $x = 0;
        $y = $height;
        $height = $y;
        
        $boundary = $this->objectMother->getBoundaryStub($x, $y, $width, $height);
        $this->invokeMethod($paragraph, 'setBoundary', array($boundary));
        
        $textGlyphs = array();

        foreach($wordsSizes as $index => $wordsSizesForGlyph)
        {
            $textGlyph = new Text();
            
            list($words, $sizes) = $wordsSizesForGlyph;
            $textGlyph->setWordsSizes($words, $sizes);
            $textGlyph->setFontSize($fontSizes[$index]);
            
            $paragraph->add($textGlyph);
            $textGlyphs[] = $textGlyph;
        }
        
        $this->formatter->format($paragraph, $this->document);
        
        foreach($textGlyphs as $i => $textGlyph)
        {
            $expectedXCoordOfFirstPoint = $expectedPositions[$i][0][0];
            $expectedYCoordOfFirstPoint = $expectedPositions[$i][0][1];            
            list($actualXCoordOfFirstPoint, $actualYCoordOfFirstPoint) = $textGlyph->getFirstPoint()->toArray();
            
            $this->assertEquals($expectedXCoordOfFirstPoint, $actualXCoordOfFirstPoint, '', 1);
            $this->assertEquals($expectedYCoordOfFirstPoint, $actualYCoordOfFirstPoint, '', 1);

            $expectedXCoordOfDiagonalPoint = $expectedPositions[$i][1][0];
            $expectedYCoordOfDiagonalPoint = $expectedPositions[$i][1][1];            
            list($actualXCoordOfDiagonalPoint, $actualYCoordOfDiagonalPoint) = $textGlyph->getDiagonalPoint()->toArray();
            
            $this->assertEquals($expectedXCoordOfDiagonalPoint, $actualXCoordOfDiagonalPoint, '', 1);
            $this->assertEquals($expectedYCoordOfDiagonalPoint, $actualYCoordOfDiagonalPoint, '', 1);
        }
    }
    
    public function dataProvider()
    {
        $lineHeightFor15 = $this->getLineHeight(15);
        $lineHeightFor12 = $this->getLineHeight(12);
        
        return array(
            array(
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
                        array(0, 200),
                        array(22, 200 - $lineHeightFor15),
                    ),
                    array(
                        array(0, 200 - $lineHeightFor15),
                        array(15, 200 - ($lineHeightFor15 + 2*$lineHeightFor12)),
                    ),
                ),
            ),
        );
    }
    
    private function getLineHeight($fontSize)
    {
        return $fontSize*1.2;
    }
}