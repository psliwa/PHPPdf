<?php

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Node\Node;
use PHPPdf\Core\Node\Container;
use PHPPdf\Core\Document;
use PHPPdf\Core\Point;
use PHPPdf\Core\Node\Text;
use PHPPdf\Core\Node\Paragraph;
use PHPPdf\Core\Formatter\ParagraphFormatter;
use PHPPdf\ObjectMother\NodeObjectMother;

class ParagraphFormatterTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $objectMother;
    
    private $formatter;
    private $document;
    
    protected function init()
    {
        $this->objectMother = new NodeObjectMother($this);
    }

    public function setUp()
    {
        $this->formatter = new ParagraphFormatter();
        $this->document = $this->createDocumentStub();
    }
    
    /**
     * @test
     * @dataProvider dataProvider
     */
    public function calculateTextsPositions($x, $width, $height, array $fontSizes, array $wordsSizes, array $expectedPositions)
    {
        $paragraph = $this->createParagraph($x, $height, $width, $height);
        $this->createTextNodesAndAddToParagraph($wordsSizes, $fontSizes, $paragraph);
        
        $this->formatter->format($paragraph, $this->document);
        
        foreach($paragraph->getChildren() as $i => $textNode)
        {
            $this->assertPointEquals($expectedPositions[$i][0], $textNode->getFirstPoint(), sprintf('%%sfirst point of "%d" text is invalid', $i));
            $this->assertPointEquals($expectedPositions[$i][1], $textNode->getDiagonalPoint(), sprintf('%%sdiagonal point of "%d" text is invalid', $i));
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
                    //expected position for 1st text
                    array(
                        //first point
                        array(2, 200),
                        //diagonal point
                        array(24, 200 - $lineHeightFor15),
                    ),
                    array(
                        array(2, 200 - $lineHeightFor15),
                        array(24, 200 - ($lineHeightFor15 + 2*$lineHeightFor12)),
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
    
    private function createTextNodesAndAddToParagraph(array $wordsSizes, array $fontSizes, Paragraph $paragraph)
    {
        foreach($wordsSizes as $index => $wordsSizesForNode)
        {
            $this->createTextNode($wordsSizesForNode, $fontSizes[$index], $paragraph);
        }
    }
    
    private function createTextNode(array $wordsSizes, $fontSize, Paragraph $paragraph)
    {
        $textNode = new Text();
        
        list($words, $sizes) = $wordsSizes;
        $textNode->setWordsSizes($words, $sizes);
        $textNode->setFontSize($fontSize);
        
        $paragraph->add($textNode);
        
        return $textNode;
    }
    
    private function getLineHeight($fontSize)
    {
        return $fontSize*1.2;
    }
    
    /**
     * @test
     */
    public function useWidthOfAncestorIfParagraphParentsWidthIsNull()
    {
        $width = 300;        
        $grandparent = $this->objectMother->getNodeStub(0, 500, $width, 100);        
        $paragraph = $this->createParagraph(0, 500, 0, 100);

        $grandparent->add($paragraph->getParent());
        
        $wordsSizes = array(10, 20, 30);
        $text = $this->createTextNode(array(
            array('ab', 'cd', 'ef'),
            $wordsSizes,
        ), 12, $paragraph);

        $this->formatter->format($paragraph, $this->document);
        
        $this->assertEquals(1, count($paragraph->getLines()));
    }
}