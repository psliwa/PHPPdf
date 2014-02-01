<?php

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Engine\Font;

use PHPPdf\Core\Node\Text;
use PHPPdf\Core\Document;
use PHPPdf\Core\Node\Node;
use PHPPdf\Core\Node\Container;
use PHPPdf\Core\Node\Page;
use PHPPdf\Core\Formatter\TextDimensionFormatter;

class TextDimensionFormatterTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    const TEXT_WIDTH = 100;
    const FONT_SIZE = 12;
    
    private $formatter;

    public function setUp()
    {
        $this->formatter = new TextDimensionFormatter();
        $this->document = $this->createDocumentStub();
    }
    
    /**
     * For simplification in this test each character width = 1
     * 
     * @test
     * @dataProvider textProvider
     */
    public function calculateSizeOfEachWord($text, $expectedWords, $expectedSizes)
    {
        $textMock = $this->createText($text);
                 
        $this->formatter->format($textMock, $this->document);
        
        $this->verifyMockObjects();
        
        $this->assertEquals($expectedWords, $textMock->getWords());
        $this->assertEquals($expectedSizes, $textMock->getWordsSizes());
    }
    
    private function createText($text)
    {
        $page = new Page(array('page-size' => self::TEXT_WIDTH.':100'));
        
        $textNode = new TextDimensionFormatterTest_Text($text, new TextDimensionFormatterTest_Font());
        $textNode->setFontSize(self::FONT_SIZE);
        $textNode->setWidth(self::TEXT_WIDTH);
        
        $page->add($textNode);
        
        return $textNode;
    }
    
    public function textProvider()
    {
        return array(
            array(
            	'some text with some words', 
                array('some ', 'text ', 'with ', 'some ', 'words'), 
                array(5, 5, 5, 5, 5), 
            ),
            array(
            	'some text with some words ', 
                array('some ', 'text ', 'with ', 'some ', 'words ', ''), 
                array(5, 5, 5, 5, 6, 0), 
            ),
            //very long word, split it!
            array(
                str_repeat('a', self::TEXT_WIDTH+5),
                array(str_repeat('a', self::TEXT_WIDTH), 'aaaaa'),
                array(self::TEXT_WIDTH, 5),
            ),
            //very very long word, split it to 3 words!
            array(
                str_repeat('a', self::TEXT_WIDTH*2+5),
                array(str_repeat('a', self::TEXT_WIDTH), str_repeat('a', self::TEXT_WIDTH), 'aaaaa'),
                array(self::TEXT_WIDTH, self::TEXT_WIDTH, 5),
            ),
            //very long word - exacly 2 * maxPossibleWidth
            array(
                str_repeat('a', self::TEXT_WIDTH*2),
                array(str_repeat('a', self::TEXT_WIDTH), str_repeat('a', self::TEXT_WIDTH)),
                array(self::TEXT_WIDTH, self::TEXT_WIDTH),
            )
        );
    }
}

class TextDimensionFormatterTest_Text extends Text
{
    private $font;
    
    public function __construct($text, Font $font)
    {
        parent::__construct($text);
        $this->font = $font;
    }
    
    public function getFont(Document $document)
    {
        return $this->font;
    }
}

class TextDimensionFormatterTest_Font implements Font
{
    public function hasStyle($style)
    {
        throw new \BadMethodCallException();
    }
    public function setStyle($style)
    {
        throw new \BadMethodCallException();
    }
    public function getCurrentStyle()
    {
        throw new \BadMethodCallException();
    }
    
    public function getCurrentResourceIdentifier()
    {
        throw new \BadMethodCallException();
    }    
    public function getWidthOfText($text, $fontSize)
    {
        return strlen($text);
    }
}