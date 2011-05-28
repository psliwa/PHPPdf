<?php

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
        $lineHeight = 12;
        $glyph->setLineHeight($lineHeight);
        $glyph->setWordsInRows(\explode(' ', $text));

        $glyph->getBoundary()->setNext(0, 200)
                             ->setNext(3, 200)
                             ->setNext(3, 104)
                             ->setNext(0, 104)
                             ->close();

        $lineSizes = array_combine(range(0, count($words) - 1), array_fill(0, count($words), 3));
        $glyph->setLineSizes($lineSizes);

        $lineSplit = 30;
        $result = $glyph->split($lineSplit);

        $this->assertEquals(24, $glyph->getHeight());
        list(,$y) = $glyph->getEndDrawingPoint();
        $this->assertEquals(176, $y);
        $this->assertEquals(2, count($glyph->getLineSizes()));

        $this->assertEquals(72, $result->getHeight());
        list(,$y) = $result->getStartDrawingPoint();
        $this->assertEquals(176 - $lineSplit % $lineHeight, $y);
        $this->assertEquals(6, count($result->getLineSizes()));
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
     * @dataProvider alignDataProvider
     */
    public function textAlign($align, $lineWidth, $excepted, $paddingLeft, $paddingRight)
    {       
        $this->page->setAttribute('text-align', $align);
        $this->page->setAttribute('padding-left', $paddingLeft);
        $this->page->setAttribute('padding-right', $paddingRight);
        $this->page->add($this->text);

        $position = $this->text->getStartLineDrawingXDimension($align, $lineWidth);

        $this->assertEquals($excepted, $position);
    }

    public function alignDataProvider()
    {
        return array(
            array('right', 100, self::PAGE_WIDTH - 120, 10, 10),
            array('right', 100, self::PAGE_WIDTH - 100, 0, 0),
            array('left', 100, 0, 0, 0),
            array('center', 100, self::PAGE_WIDTH / 2 - 20/2 - 100/2, 10, 10),
            array('center', 200, self::PAGE_WIDTH / 2 - 20/2 - 200/2, 20, 0),
        );
    }

    /**
     * @test
     * @dataProvider lineSizesProvider
     */
    public function minimumWidthIsTheWidestTextRow(array $lineSizes)
    {
        $this->text->setLineSizes($lineSizes);

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
}