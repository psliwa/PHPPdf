<?php

use PHPPdf\Glyph\Table;
use PHPPdf\Util\Boundary;
use PHPPdf\Glyph as Glyphs;

class TableTest extends TestCase
{
    private $table = null;
    private $objectMother;

    public function init()
    {
        $this->objectMother = new TableObjectMother($this);
    }

    public function setUp()
    {
        $this->table = new Table();
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function addingInvalidChild()
    {
        $glyph = new Glyphs\Container();
        $this->table->add($glyph);
    }

    /**
     * @test
     */
    public function addingValidChild()
    {
        $glyph = new Glyphs\Table\Row();
        $this->table->add($glyph);

        $this->assertTrue(count($this->table->getChildren()) > 0);
    }

    /**
     * @test
     */
    public function rowsAndCellsAttributes()
    {
        $height = 40;
        $this->table->setRowHeight($height);
        
        $this->assertEquals($height, $this->table->getRowHeight());
    }
    
    /**
     * @test
     */
    public function split()
    {
        $numberOfRows = 10;
        $heightOfRow = 50;
        $tableHeight = 500;

        $boundary = $this->table->getBoundary();
        $boundary->setNext(0, $tableHeight)
                 ->setNext(200, $tableHeight)
                 ->setNext(200, 0)
                 ->setNext(0, 0)
                 ->close();

        $this->table->setHeight(500)->setWidth(200);

        $start = 500;
        $pointOfSplit = 220;
        $reversePointOfSplit = $start - $pointOfSplit;
        $rowSplitOccurs = false;
        for($i=0; $i<$numberOfRows; $i++)
        {
            $end = $start-$heightOfRow;
            $split = $reversePointOfSplit < $start && $reversePointOfSplit > $end;
            if($split)
            {
                $rowSplitOccurs = true;
            }

            $mock = $this->createRowMock(array(0, $start), array(200, $end), $split, $rowSplitOccurs);
            $this->table->add($mock);
            $start = $end;
        }

        $result = $this->table->split(220);

        $this->assertNotNull($result);
        $this->assertEquals($numberOfRows, count($result->getChildren()) + count($this->table->getChildren()));
        $this->assertEquals($tableHeight, $result->getHeight() + $this->table->getHeight());
    }

    private function createRowMock($start, $end, $split = false, $translate = false)
    {
        $methods = array('getHeight', 'getBoundary');
        if($split)
        {
            $methods[] = 'split';
        }
        if($translate)
        {
            $methods[] = 'translate';
        }

        $mock = $this->getMock('PHPPdf\Glyph\Table\Row', $methods);

        if($split)
        {
            $mock->expects($this->once())
                 ->method('split')
                 ->will($this->returnValue(null));
        }

        $boundary = new Boundary();
        $boundary->setNext($start[0], $start[1])
                 ->setNext($end[0], $start[1])
                 ->setNext($end[0], $end[1])
                 ->setNext($start[0], $end[1])
                 ->close();

        $height = $start[1] - $end[1];

        $mock->expects($this->atLeastOnce())
             ->method('getBoundary')
             ->will($this->returnValue($boundary));
        $mock->expects($this->any())
             ->method('getHeight')
             ->will($this->returnValue($height));

        if($translate)
        {
            $mock->expects($this->atLeastOnce())
                 ->method('translate');
        }

        return $mock;
    }

    /**
     * @test
     * @dataProvider cellsInRowsWidthsProvider
     */
    public function setColumnsWidthsWhenTableIsNotifiedByCell(array $cellsWidthsByColumn)
    {
        $cells = array();
        $columnsWidths = array();
        foreach($cellsWidthsByColumn as $columnNumber => $cellsWidths)
        {
            foreach($cellsWidths as $width)
            {
                $cell = $this->getMock('PHPPdf\Glyph\Table\Cell', array('getWidth', 'getNumberOfColumn'));
                $cell->expects($this->atLeastOnce())
                     ->method('getWidth')
                     ->will($this->returnValue($width));
                $cell->expects($this->atLeastOnce())
                     ->method('getNumberOfColumn')
                     ->will($this->returnValue($columnNumber));

                $cells[] = $cell;

                if(!isset($columnsWidths[$columnNumber]) || $width > $columnsWidths[$columnNumber])
                {
                    $columnsWidths[$columnNumber] = $width;
                }
            }
        }

        foreach($cells as $cell)
        {
            $this->table->attributeChanged($cell, 'width', null);
        }

        $this->assertEquals($columnsWidths, $this->table->getWidthsOfColumns());
    }

    public function cellsInRowsWidthsProvider()
    {
        return array(
            array(
                array(
                    array(100, 200, 110),
                    array(30, 50, 30),
                ),
            ),
        );
    }

    /**
     * @test
     */
    public function setColumnsWidthsWhenRowHasAdded()
    {
        $cellWidth = 120;
        $row = $this->getMock('PHPPdf\Glyph\Table\Row', array('getChildren'));
        $cell = $this->getMock('PHPPdf\Glyph\Table\Cell', array('getWidth', 'getNumberOfColumn'));

        $row->expects($this->atLeastOnce())
            ->method('getChildren')
            ->will($this->returnValue(array($cell)));
        $cell->expects($this->atLeastOnce())
             ->method('getWidth')
             ->will($this->returnValue($cellWidth));
        $cell->expects($this->atLeastOnce())
             ->method('getNumberOfColumn')
             ->will($this->returnValue(0));


        $this->table->add($row);

        $this->assertEquals(array($cellWidth), $this->table->getWidthsOfColumns());
    }

    /**
     * @test
     * @dataProvider cellsInRowsWidthsProvider
     */
    public function minWidthOfColumnIsMaxOfMinWidthOfColumnsCells(array $cellsInRowsMinWidths)
    {
        $expectedMinWidthsOfColumns = array_fill(0, count($cellsInRowsMinWidths[0]), 0);
        foreach($cellsInRowsMinWidths as $cellsMinWidths)
        {
            $row = $this->getMock('PHPPdf\Glyph\Table\Row', array('getChildren'));

            $cells = array();
            foreach ($cellsMinWidths as $i => $minWidth)
            {
                $cell = $this->getMock('PHPPdf\Glyph\Table\Cell', array('getMinWidth', 'getNumberOfColumn'));
                $cell->expects($this->atLeastOnce())
                     ->method('getMinWidth')
                     ->will($this->returnValue($minWidth));
                $cell->expects($this->atLeastOnce())
                     ->method('getNumberOfColumn')
                     ->will($this->returnValue($i));
                $cells[] = $cell;

                $expectedMinWidthsOfColumns[$i] = max($expectedMinWidthsOfColumns[$i], $minWidth);
            }
            $row->expects($this->atLeastOnce())
                ->method('getChildren')
                ->will($this->returnValue($cells));
            $this->table->add($row);
        }

        $this->assertEquals($expectedMinWidthsOfColumns, $this->table->getMinWidthsOfColumns());
    }
}