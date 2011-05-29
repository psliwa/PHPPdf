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
     * @dataProvider cellsInRowsWidthsAndColspansProvider
     */
    public function setColumnsWidthsWhenTableHasBeenNotifiedByCell(array $cellsWidthsByColumn, array $colspans)
    {
        $cells = array();
        $columnsWidths = array_fill(0, count($cellsWidthsByColumn), 0);
        foreach($cellsWidthsByColumn as $columnNumber => $cellsWidths)
        {
            foreach($cellsWidths as $rowNumber => $width)
            {
                $cell = $this->getMock('PHPPdf\Glyph\Table\Cell', array('getWidth', 'getNumberOfColumn', 'getColspan'));
                $cell->expects($this->atLeastOnce())
                     ->method('getWidth')
                     ->will($this->returnValue($width));
                $cell->expects($this->atLeastOnce())
                     ->method('getNumberOfColumn')
                     ->will($this->returnValue($columnNumber));

                $colspan = $colspans[$columnNumber][$rowNumber];

                $cell->expects($this->atLeastOnce())
                     ->method('getColspan')
                     ->will($this->returnValue($colspan));

                $cells[] = $cell;

                $widthPerColumn = $width / $colspan;
                for($i=0; $i < $colspan; $i++)
                {
                    $realColumnNumber = $columnNumber + $i;
                    $columnsWidths[$realColumnNumber] = max($widthPerColumn, $columnsWidths[$realColumnNumber]);
                }
            }
        }

        foreach($cells as $cell)
        {
            $this->table->attributeChanged($cell, 'width', null);
        }

        $this->assertEquals($columnsWidths, $this->table->getWidthsOfColumns());
    }

    public function cellsInRowsWidthsAndColspansProvider()
    {
        return array(
            array(
                array(
                    array(100, 200, 110), 
                    array(30, 50, 30),
                ),
                array(
                    array(1, 1, 1),
                    array(1, 1, 1),
                ),
            ),
            array(
                array(
                    array(100, 70, 200),
                    array(30, 50),
                ),
                array(
                    array(1, 1, 2),
                    array(1, 1),
                ),
            ),
        );
    }

    /**
     * @test
     * @dataProvider cellsInRowsMarginsProvider
     */
    public function setColumnsMarginsWhenTableHasBeenNotifiedByCell(array $cellsMarginsLeft, array $cellsMarginsRight)
    {
        $numberOfColumns = count($cellsMarginsLeft);
        $expectedMarginsLeft = array_fill(0, $numberOfColumns, 0);
        $expectedMarginsRight = array_fill(0, $numberOfColumns, 0);
        
        foreach($cellsMarginsLeft as $columnNumber => $marginsLeft)
        {
            foreach($marginsLeft as $rowNumber => $marginLeft)
            {
                $marginRight = $cellsMarginsRight[$columnNumber][$rowNumber];
                $cell = $this->getMock('PHPPdf\Glyph\Table\Cell', array('getMarginLeft', 'getMarginRight', 'getNumberOfColumn'));
                $cell->expects($this->atLeastOnce())
                     ->method('getMarginLeft')
                     ->will($this->returnValue($marginLeft));
                $cell->expects($this->atLeastOnce())
                     ->method('getMarginRight')
                     ->will($this->returnValue($marginRight));
                $cell->expects($this->atLeastOnce())
                     ->method('getNumberOfColumn')
                     ->will($this->returnValue($columnNumber));

                $expectedMarginsLeft[$columnNumber] = max($expectedMarginsLeft[$columnNumber], $marginLeft);
                $expectedMarginsRight[$columnNumber] = max($expectedMarginsRight[$columnNumber], $marginRight);

                $cells[] = $cell;
            }
        }

        foreach($cells as $cell)
        {
            $this->table->attributeChanged($cell, 'margin-left', 0);
            $this->table->attributeChanged($cell, 'margin-right', 0);
        }

        $this->assertEquals($expectedMarginsLeft, $this->table->getMarginsLeftOfColumns());
        $this->assertEquals($expectedMarginsRight, $this->table->getMarginsRightOfColumns());
    }

    public function cellsInRowsMarginsProvider()
    {
        return array(
            array(
                array(
                    array(10, 5, 11),
                    array(0, 23, 6),
                ), array(
                    array(5, 10, 9),
                    array(8, 11, 19),
                )
            ),
        );
    }

    /**
     * @test
     * @dataProvider setColumnsWidthsWhenRowHasBeenAddedProvider
     */
    public function setColumnsWidthsWhenRowHasBeenAdded($cellWidth, $colspan, $expectedColumnsWidth)
    {
        $row = $this->getMock('PHPPdf\Glyph\Table\Row', array('getChildren'));
        $cell = $this->getMock('PHPPdf\Glyph\Table\Cell', array('getWidth', 'getNumberOfColumn', 'getColspan'));

        $row->expects($this->atLeastOnce())
            ->method('getChildren')
            ->will($this->returnValue(array($cell)));
        $cell->expects($this->atLeastOnce())
             ->method('getWidth')
             ->will($this->returnValue($cellWidth));
        $cell->expects($this->atLeastOnce())
             ->method('getNumberOfColumn')
             ->will($this->returnValue(0));
        $cell->expects($this->atLeastOnce())
             ->method('getColspan')
             ->will($this->returnValue($colspan));

        $this->table->add($row);

        $this->assertEquals($expectedColumnsWidth, $this->table->getWidthsOfColumns());
    }
    
    public function setColumnsWidthsWhenRowHasBeenAddedProvider()
    {
        return array(
            array(120, 1, array(120)),
            array('70%', 1, array('70%')),
            array('70%', 2, array('35%', '35%')),
        );
    }
    
    /**
     * @test
     * @dataProvider convertColumnWidthsFromRelativeToAbsoluteProvider
     */    
    public function convertColumnWidthsFromRelativeToAbsolute($tableWidth, $actualWidthOfColumns, $expectedWidthOfColumns)
    {
        $this->table->setWidth($tableWidth);
        $this->invokeMethod($this->table, 'setWidthsOfColumns', array($actualWidthOfColumns));
        
        $this->table->convertRelativeWidthsOfColumns();
        
        $this->assertEquals($expectedWidthOfColumns, $this->table->getWidthsOfColumns());
    }
    
    public function convertColumnWidthsFromRelativeToAbsoluteProvider()
    {
        return array(
            array(100, array(100), array(100)),
            array(200, array('25%', '25%'), array(50, 50)),
        );
    }

    /**
     * @test
     * @dataProvider cellsInRowsWidthsProvider
     */
    public function minWidthOfColumnIsMaxOfMinWidthOfColumnsCells(array $cellsInRowsMinWidths, array $colspans, $numberOfColumns)
    {
        $expectedMinWidthsOfColumns = array_fill(0, $numberOfColumns, 0);
        foreach($cellsInRowsMinWidths as $rowNumber => $cellsMinWidths)
        {
            $row = $this->getMock('PHPPdf\Glyph\Table\Row', array('getChildren'));

            $cells = array();
            foreach ($cellsMinWidths as $columnNumber => $minWidth)
            {
                $colspan = $colspans[$rowNumber][$columnNumber];

                $cell = $this->getMock('PHPPdf\Glyph\Table\Cell', array('getMinWidth', 'getNumberOfColumn', 'getColspan'));
                $cell->expects($this->atLeastOnce())
                     ->method('getMinWidth')
                     ->will($this->returnValue($minWidth));
                $cell->expects($this->atLeastOnce())
                     ->method('getNumberOfColumn')
                     ->will($this->returnValue($columnNumber));
                $cell->expects($this->atLeastOnce())
                     ->method('getColspan')
                     ->will($this->returnValue($colspan));
                $cells[] = $cell;

                $minWidthPerColumn = $minWidth / $colspan;
                for($i=0; $i<$colspan; $i++)
                {
                    $realColumnNumber = $columnNumber + $i;
                    $expectedMinWidthsOfColumns[$realColumnNumber] = max($expectedMinWidthsOfColumns[$realColumnNumber], $minWidthPerColumn);
                }
            }
            $row->expects($this->atLeastOnce())
                ->method('getChildren')
                ->will($this->returnValue($cells));
            $this->table->add($row);
        }

        $this->assertEquals($expectedMinWidthsOfColumns, $this->table->getMinWidthsOfColumns());
    }

    public function cellsInRowsWidthsProvider()
    {
        return array(
            array(
                array(
                    array(100, 200, 110),
                    array(30, 50, 30),
                ),
                array(
                    array(1, 1, 1,),
                    array(1, 1, 1,),
                ),
                3
            ),
            array(
                array(
                    array(100, 200),
                    array(30, 50, 30),
                ),
                array(
                    array(1, 2),
                    array(1, 1, 1,),
                ),
                3
            ),
        );
    }

    /**
     * @test
     */
    public function reduceColumnsWidthsByMargins()
    {
        $columnsWidths = array(100, 200, 150);
        $marginsLeft = array(0, 5, 10);
        $marginsRight = array(5, 5, 0);

        $this->invokeMethod($this->table, 'setWidthsOfColumns', array($columnsWidths));
        $this->invokeMethod($this->table, 'setMarginsLeftOfColumns', array($marginsLeft));
        $this->invokeMethod($this->table, 'setMarginsRightOfColumns', array($marginsRight));

        $this->table->reduceColumnsWidthsByMargins();

        array_walk($columnsWidths, function(&$value, $key) use($marginsLeft, $marginsRight)
        {
            $value -= $marginsLeft[$key] + $marginsRight[$key];
        });

        $this->assertEquals($columnsWidths, $this->table->getWidthsOfColumns());
    }
}