<?php

use PHPPdf\Glyph\Table\Row;
use PHPPdf\Glyph as Glyphs;

class RowTest extends TestCase
{
    private $row = null;

    public function setUp()
    {
        $this->row = new Row();
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function addingInvalidChild()
    {
        $glyph = new Glyphs\Container();
        $this->row->add($glyph);
    }
    
    /**
     * @test
     */
    public function addingValidChild()
    {
        $glyph = new Glyphs\Table\Cell();
        $this->row->add($glyph);

        $this->assertTrue(count($this->row->getChildren()) > 0);
    }
    
    /**
     * @test
     */
    public function split()
    {
        $boundary = $this->row->getBoundary();
        $boundary->setNext(0, 100)
                 ->setNext(100, 100)
                 ->setNext(100, 0)
                 ->setNext(0, 0)
                 ->close();

        $this->assertNull($this->row->split(50));
    }

    /**
     * @test
     */
    public function getHeightFromTable()
    {
        $tableMock = $this->getMock('PHPPdf\Glyph\Table', array(
            'getRowHeight'
        ));

        $rowHeight = 45;
        $tableMock->expects($this->once())
                  ->method('getRowHeight')
                  ->will($this->returnValue($rowHeight));

        $tableMock->add($this->row);

        $this->assertEquals($rowHeight, $this->row->getHeight());
    }
    
    /**
     * @test
     */
    public function getWidthFromTable()
    {
        $tableMock = $this->getMock('PHPPdf\Glyph\Table', array(
            'getWidth'
        ));

        $width = 200;
        $tableMock->expects($this->exactly(2))
                  ->method('getWidth')
                  ->will($this->returnValue($width));

        $tableMock->add($this->row);

        $this->assertEquals($width, $this->row->getWidth());
        $this->row->setWidth(5);
        $this->assertEquals($width, $this->row->getWidth());
    }

    /**
     * @test
     */
    public function setNumberOfColumnForCells()
    {
        for($i=0; $i<2; $i++)
        {
            $cell = $this->getMock('PHPPdf\Glyph\Table\Cell', array('setNumberOfColumn'));
            $cell->expects($this->once())
                 ->method('setNumberOfColumn')
                 ->with($i);

            $cells[] = $cell;
        }

        foreach($cells as $cell)
        {
            $this->row->add($cell);
        }
    }
}