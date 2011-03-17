<?php

use PHPPdf\Document;
use PHPPdf\Glyph\AbstractGlyph;
use PHPPdf\Glyph\Container;
use PHPPdf\Glyph\Page;
use PHPPdf\Formatter\TextDimensionFormatter;
use PHPPdf\Font\Font;
use PHPPdf\Font\ResourceWrapper;

class TextDimensionFormatterTest extends PHPUnit_Framework_TestCase
{
    private $formatter;

    public function setUp()
    {
        $this->formatter = new TextDimensionFormatter();
        $this->document = new Document();
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
        $this->formatter->format($text, $this->document);

        $height = $text->getHeight();

        $this->assertNotEquals(1, $text->getWidth());

        $text->reset();

        $text->getBoundary()->setNext(0, $page->getHeight());
        
        $text->setText('a a a');

        $this->formatter->format($text, $this->document);

        $this->assertEquals(3*$height, $text->getHeight());
    }

    private function getPageStub()
    {
        $page = new Page();
        $page['font-type'] = new Font(array(
            Font::STYLE_NORMAL => ResourceWrapper::fromName(\Zend_Pdf_Font::FONT_COURIER)
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

        $this->formatter->format($text, $this->document);

        $text2->setText('ae');
        $page->add($text2);

        $text2->getBoundary()->setNext(0, $page->getHeight()/2);

        $this->formatter->format($text2, $this->document);

        $this->assertEquals($text->getWidth(), $text2->getWidth());
    }
}