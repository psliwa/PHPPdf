<?php

use PHPPdf\Formatter\TableFormatter;
use PHPPdf\Document;
use PHPPdf\Util\Boundary;

class TableFormatterTest extends TestCase
{
    private $formatter;
    private $objectMother;

    protected function init()
    {
        $this->objectMother = new TableObjectMother($this);
    }

    public function setUp()
    {
        $this->formatter = new TableFormatter();
    }

    /**
     * @test
     * @dataProvider cellsWidthProvider
     */
    public function equalizeCells(array $cellsWidthInRows, array $minWidthsOfColumns, array $columnsWidths, $tableWidth)
    {
        $totalWidth = array_sum($columnsWidths);

        $rows = array();
        foreach($cellsWidthInRows as $widths)
        {
            $diffBetweenTableAndColumnsWidths = $tableWidth - $totalWidth;
            $translate = 0;
            $cells = array();
            foreach($widths as $column => $width)
            {
                $columnWidth = $columnsWidths[$column];
                $minWidth = $minWidthsOfColumns[$column];
                $widthMargin = $columnWidth - $minWidth;

                if($diffBetweenTableAndColumnsWidths < 0 && -$diffBetweenTableAndColumnsWidths >= $widthMargin)
                {
                    $columnWidth = $minWidth;
                    $diffBetweenTableAndColumnsWidths += $widthMargin;
                }
                elseif($diffBetweenTableAndColumnsWidths < 0)
                {
                    $columnWidth += $diffBetweenTableAndColumnsWidths;
                    $diffBetweenTableAndColumnsWidths = 0;
                }

                $cells[] = $this->objectMother->getCellMockWithTranslateAndResizeExpectations($width, $columnWidth, $translate);
                $translate += $columnWidth;
            }

            $row = $this->getMock('PHPPdf\Glyph\Table\Row', array('getChildren'));
            $row->expects($this->atLeastOnce())
                ->method('getChildren')
                ->will($this->returnValue($cells));
            $rows[] = $row;
        }

        $table = $this->getMock('PHPPdf\Glyph\Table', array('getChildren', 'getWidthsOfColumns', 'getMinWidthsOfColumns', 'getWidth'));
        $table->expects($this->atLeastOnce())
              ->method('getChildren')
              ->will($this->returnValue($rows));
        $table->expects($this->atLeastOnce())
              ->method('getWidthsOfColumns')
              ->will($this->returnValue($columnsWidths));
        $table->expects($this->atLeastOnce())
              ->method('getMinWidthsOfColumns')
              ->will($this->returnValue($minWidthsOfColumns));
        $table->expects($this->atLeastOnce())
              ->method('getWidth')
              ->will($this->returnValue($tableWidth));

        $this->formatter->format($table, new Document());
    }

    public function cellsWidthProvider()
    {
        return array(
            array(
                array(
                    array(10, 20, 30),
                    array(40, 10, 15),
                ),
                array(0, 0, 0),
                array(50, 20, 30),
                100
            ),
            array(
                array(
                    array(10, 20, 30),
                    array(40, 10, 15),
                ),
                array(5, 10, 0),
                array(50, 20, 30),
                90
            ),
        );
    }
}