<?php

use PHPPdf\Document,
    PHPPdf\Util\Point,
    PHPPdf\Glyph\Glyph,
    PHPPdf\Glyph\Container,
    PHPPdf\Glyph\Page,
    PHPPdf\Formatter\StandardPositionFormatter;

class StandardPositionFormatterTest extends PHPUnit_Framework_TestCase
{
    private $formatter;

    public function setUp()
    {
        $this->formatter = new StandardPositionFormatter();
    }

    /**
     * @test
     */
    public function glyphWithAutoMarginPositioning()
    {
        $glyph = new Container(array('width' => 100, 'height' => 100));
        $glyph->hadAutoMargins(true);
        $glyph->makeAttributesSnapshot();
        $glyph->setWidth(110);

        $child = new Container(array('width' => 50, 'height' => 50));
        $glyph->add($child);
        $page = new Page();
        $page->add($glyph);

        $glyph->getBoundary()->setNext($page->getFirstPoint());
        $child->getBoundary()->setNext($page->getFirstPoint());

        foreach(array($glyph, $child) as $g)
        {
            $this->formatter->format($g, new Document());
        }

        $glyphBoundary = $glyph->getBoundary();
        $childBoundary = $child->getBoundary();
        $pageBoundary = $page->getBoundary();


        $this->assertEquals($pageBoundary[0]->translate(-5, 0), $glyphBoundary[0]);
        $this->assertEquals($pageBoundary[0]->translate(105, 0), $glyphBoundary[1]);
        $this->assertEquals($pageBoundary[0]->translate(105, 100), $glyphBoundary[2]);
        $this->assertEquals($pageBoundary[0]->translate(-5, 100), $glyphBoundary[3]);
    }
}