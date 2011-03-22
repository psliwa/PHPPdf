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

    /**
     * @test
     */
    public function tableGetter()
    {
        $table = $this->getMock('PHPPdf\Glyph\Table');
        $row = $this->getMock('PHPPdf\Glyph\Table\Row', array('getParent'));
        $row->expects($this->once())
            ->method('getParent')
            ->will($this->returnValue($table));

        $this->cell->setParent($row);

        $this->assertTrue($table === $this->cell->getTable());
    }

    /**
     * @test
     */
    public function notifyListenersWhenAttributeHasChanged()
    {
        $listener = $this->getMock('PHPPdf\Glyph\AttributeListener', array('attributeChanged'));
        
        $listener->expects($this->at(0))
                 ->method('attributeChanged')
                 ->with($this->cell, 'width', 0);

        $listener->expects($this->at(1))
                 ->method('attributeChanged')
                 ->with($this->cell, 'width', 100);

        $this->cell->addAttributeListener($listener);

        $this->cell->setAttribute('width', 100);
        $this->cell->setAttribute('width', 200);
    }
}