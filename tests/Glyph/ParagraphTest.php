<?php

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
    }
    
    public function textProvider()
    {
        return array(
            array(
                array('some text ', ' some another text'),
            ),
        );
    }
}