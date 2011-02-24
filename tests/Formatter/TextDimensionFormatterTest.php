<?php

use PHPPdf\Document;
use PHPPdf\Glyph\AbstractGlyph;
use PHPPdf\Glyph\Container;
use PHPPdf\Glyph\Page;
use PHPPdf\Formatter\TextDimensionFormatter;
use PHPPdf\Font\Font;

class TextDimensionFormatterTest extends PHPUnit_Framework_TestCase
{
    private $formatter;

    public function setUp()
    {
        $this->formatter = new TextDimensionFormatter(new Document());
    }

    /**
     * @test
     */
    public function glyphFormatter()
    {
        $page = $this->getPageStub();
        $text = new PHPPdf\Glyph\Text('a', array('width' => 1, 'display' => 'block'));
        $page->add($text);

        $text->getBoundary()->setNext(0, $page->getHeight());
        $this->formatter->preFormat($text);
        $this->formatter->postFormat($text);

        $height = $text->getHeight();

        $this->assertNotEquals(1, $text->getWidth());

        $text->reset();

        $text->getBoundary()->setNext(0, $page->getHeight());
        
        $text->setText('a a a');

        $this->formatter->preFormat($text);
        $this->formatter->postFormat($text);

        $this->assertEquals(3*$height, $text->getHeight());
    }

    private function getPageStub()
    {
        $page = new Page();
        $page['font-type'] = new Font(array(
            Font::STYLE_NORMAL => \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_COURIER)
        ));
        $page['font-size'] = 12;

        return $page;
    }

    /**
     * @test
     */
    public function calculateWidthWithNoAsciChars()
    {
        $page = $this->getPageStub();

        $text = new PHPPdf\Glyph\Text('ąę', array('display' => 'inline'));
        $text2 = $text->copy();
        $page->add($text);

        $text->getBoundary()->setNext(0, $page->getHeight());

        $this->formatter->preFormat($text);
        $this->formatter->postFormat($text);

        $text2->setText('ae');
        $page->add($text2);

        $text2->getBoundary()->setNext(0, $page->getHeight()/2);

        $this->formatter->preFormat($text2);
        $this->formatter->postFormat($text2);

        $this->assertEquals($text->getWidth(), $text2->getWidth());
    }
}