<?php

use PHPPdf\Glyph\Table\Cell;
use PHPPdf\Glyph\AbstractGlyph;
use PHPPdf\Glyph\Table;

class CellTest extends PHPUnit_Framework_TestCase
{
    private $cell;

    public function setUp()
    {
        $this->cell = new Cell();
    }

    /**
     * @test
     */
    public function unmodifableFloat()
    {
        $this->assertEquals(AbstractGlyph::FLOAT_LEFT, $this->cell->getFloat());
        $this->cell->setFloat(AbstractGlyph::FLOAT_RIGHT);
        $this->assertEquals(AbstractGlyph::FLOAT_LEFT, $this->cell->getFloat());
    }

    /**
     * @test
     */
    public function defaultWidth()
    {
        $this->assertTrue($this->cell->getWidth() === 0);
    }
}