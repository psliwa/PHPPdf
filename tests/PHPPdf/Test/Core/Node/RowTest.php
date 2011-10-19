<?php

namespace PHPPdf\Test\Core\Node;

use PHPPdf\Core\Node\Table\Row;
use PHPPdf\Core\Node as Nodes;

class RowTest extends \PHPPdf\PHPUnit\Framework\TestCase
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
        $node = new Nodes\Container();
        $this->row->add($node);
    }
    
    /**
     * @test
     */
    public function addingValidChild()
    {
        $node = new Nodes\Table\Cell();
        $this->row->add($node);

        $this->assertTrue(count($this->row->getChildren()) > 0);
    }
    
    /**
     * @test
     */
    public function breakAt()
    {
        $boundary = $this->row->getBoundary();
        $boundary->setNext(0, 100)
                 ->setNext(100, 100)
                 ->setNext(100, 0)
                 ->setNext(0, 0)
                 ->close();
        $this->row->setHeight(100);

        $this->assertNull($this->row->breakAt(50));
    }

    /**
     * @test
     */
    public function getHeightFromTable()
    {
        $tableMock = $this->getMock('PHPPdf\Core\Node\Table', array(
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
        $tableMock = $this->getMock('PHPPdf\Core\Node\Table', array(
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
     * @dataProvider colspanProvider
     */
    public function setNumberOfColumnForCells(array $colspans)
    {
        $i = 0;
        foreach($colspans as $colspan)
        {
            $cell = $this->getMock('PHPPdf\Core\Node\Table\Cell', array('setNumberOfColumn', 'getColspan'));
            $cell->expects($this->atLeastOnce())
                 ->method('getColspan')
                 ->will($this->returnValue($colspan));
            $cell->expects($this->once())
                 ->method('setNumberOfColumn')
                 ->with($i);

            $cells[] = $cell;
            $i += $colspan;
        }

        foreach($cells as $cell)
        {
            $this->row->add($cell);
        }
    }

    public function colspanProvider()
    {
        return array(
            array(
                array(1, 1),
//                array(2, 1),
            ),
        );
    }

    /**
     * @test
     */
    public function addTableAsListenerWhenCellHasAddedToRow()
    {
        $table = $this->getMock('PHPPdf\Core\Node\Table');
        $cell = $this->cellWithAddListenerExpectation($table);

        $this->row->setParent($table);
        $this->row->add($cell);
    }
    
    private function cellWithAddListenerExpectation($listener)
    {
        $cell = $this->getMock('PHPPdf\Core\Node\Table\Cell', array('addListener'));

        $cell->expects($this->at(0))
             ->method('addListener')
             ->with($listener);

        return $cell;
    }

    /**
     * @test
     */
    public function addRowAsListenerWhenCellHasAddedToRow()
    {
        $cell = $this->cellWithAddListenerExpectation($this->row);

        $this->row->add($cell);
    }

    /**
     * @test
     * @dataProvider cellsHeightsProvider
     */
    public function setMaxHeightWhenRowIsNotifiedByCell(array $heights)
    {
        $cells = $this->createMockedCellsWithHeights($heights);

        foreach($cells as $cell)
        {
            $this->row->attributeChanged($cell, 'height', null);
        }

        $this->assertEquals(max($heights), $this->row->getMaxHeightOfCells());
    }

    public function cellsHeightsProvider()
    {
        return array(
            array(
                array(10, 20, 30, 20, 10),
            ),
        );
    }

    private function createMockedCellsWithHeights(array $heights)
    {
        $cells = array();
        foreach($heights as $height)
        {
            $cell = $this->getMock('PHPPdf\Core\Node\Table\Cell', array('getHeight'));
            $cell->expects($this->atLeastOnce())
                 ->method('getHeight')
                 ->will($this->returnValue($height));
            $cells[] = $cell;
        }

        return $cells;
    }

    /**
     * @test
     * @dataProvider cellsHeightsProvider
     */
    public function setMaxHeightWhileCellAdding(array $heights)
    {
        $cells = $this->createMockedCellsWithHeights($heights);

        foreach($cells as $cell)
        {
            $this->row->add($cell);
        }

        $this->assertEquals(max($heights), $this->row->getMaxHeightOfCells());
    }

    /**
     * @test
     * @dataProvider marginsDataProvider
     */
    public function setMaxVerticalMarginsWhileCellAdding(array $marginsTop, array $marginsBottom)
    {
        $cells = $this->createMockedCellsWidthVerticalMargins($marginsTop, $marginsBottom);

        foreach($cells as $cell)
        {
            $this->row->add($cell);
        }

        $this->assertEquals(max($marginsTop), $this->row->getMarginsTopOfCells());
        $this->assertEquals(max($marginsBottom), $this->row->getMarginsBottomOfCells());
    }

    public function marginsDataProvider()
    {
        return array(
            array(
                array(10, 12, 5),
                array(5, 1, 8),
            ),
        );
    }

    private function createMockedCellsWidthVerticalMargins($marginsTop, $marginsBottom)
    {
        $cells = array();

        for($i=0, $count = count($marginsTop); $i<$count; $i++)
        {
            $cell = $this->getMock('PHPPdf\Core\Node\Table\Cell', array('getMarginTop', 'getMarginBottom'));
            $cell->expects($this->atLeastOnce())
                 ->method('getMarginTop')
                 ->will($this->returnValue($marginsTop[$i]));
            $cell->expects($this->atLeastOnce())
                 ->method('getMarginBottom')
                 ->will($this->returnValue($marginsBottom[$i]));

            $cells[] = $cell;
        }

        return $cells;
    }
}