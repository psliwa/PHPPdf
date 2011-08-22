<?php

use PHPPdf\Document;
use PHPPdf\Util\Boundary;
use PHPPdf\Glyph\Glyph;
use PHPPdf\Glyph\Container;
use PHPPdf\Formatter\ContainerDimensionFormatter;

class ContainerDimensionFormatterTest extends TestCase
{
    private $formatter;
    private $objectMother;

    protected function init()
    {
        $this->objectMother = new GenericGlyphObjectMother($this);
    }
    
    public function setUp()
    {
        $this->formatter = new ContainerDimensionFormatter();
    }

    /**
     * @test
     */
    public function glyphFormatter()
    {
        $composeGlyph = new Container();
        $composeGlyph->setWidth(140);
        $children = array();
        $children[] = $this->objectMother->getGlyphStub(0, 500, 100, 200);
        $children[] = $this->objectMother->getGlyphStub(0, 300, 200, 200);

        foreach($children as $child)
        {
            $composeGlyph->add($child);
        }

        $this->formatter->format($composeGlyph, new Document());

        $height = 0;
        foreach($children as $child)
        {
            $height += $child->getHeight();
        }

        $this->assertEquals($height, $composeGlyph->getHeight());
        $this->assertEquals(200, $composeGlyph->getWidth());
    }
}