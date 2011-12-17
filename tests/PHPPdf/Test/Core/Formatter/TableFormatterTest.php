<?php

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Formatter\TableFormatter,
    PHPPdf\Core\Document,
    PHPPdf\Core\Boundary,
    PHPPdf\Core\Node\Table\Row,
    PHPPdf\Core\Node\Table,
    PHPPdf\ObjectMother\TableObjectMother,
    PHPPdf\Core\Node\Table\Cell;

class TableFormatterTest extends \PHPPdf\PHPUnit\Framework\TestCase
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
    public function equalizeCells(array $cellsWidthInRows, array $minWidthsOfColumns, array $columnsWidths, array $columnsMarginsLeft, array $columnsMarginsRight, $tableWidth)
    {
        $totalWidth = array_sum($columnsWidths);
        $numberOfColumns = count($columnsWidths);

        $rows = array();
        foreach($cellsWidthInRows as $widths)
        {
            $diffBetweenTableAndColumnsWidths = $tableWidth - $totalWidth - array_sum($columnsMarginsLeft) - array_sum($columnsMarginsRight);
            $translate = 0;
            $cells = array();
            foreach($widths as $column => $width)
            {
                $bothMargins = $columnsMarginsLeft[$column] + $columnsMarginsRight[$column];
                $columnWidth = $columnsWidths[$column];
                $minWidth = $minWidthsOfColumns[$column] + $bothMargins;
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

                $translate += $columnsMarginsLeft[$column];
                $cell = $this->objectMother->getCellMockWithTranslateAndResizeExpectations($width, $columnWidth, $translate);
                $cell->expects($this->atLeastOnce())
                     ->method('getNumberOfColumn')
                     ->will($this->returnValue($column));
                $cells[] = $cell;
                $translate += $columnWidth + $columnsMarginsRight[$column];
            }

            $row = $this->getMock('PHPPdf\Core\Node\Table\Row', array('getChildren'));
            $row->expects($this->atLeastOnce())
                ->method('getChildren')
                ->will($this->returnValue($cells));
            $rows[] = $row;
        }

        $table = $this->getMock('PHPPdf\Core\Node\Table', array('getChildren', 'getWidthsOfColumns', 'getMinWidthsOfColumns', 'getWidth', 'getMarginsLeftOfColumns', 'getMarginsRightOfColumns'));
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

        $table->expects($this->atLeastOnce())
              ->method('getMarginsLeftOfColumns')
              ->will($this->returnValue($columnsMarginsLeft));
        $table->expects($this->atLeastOnce())
              ->method('getMarginsRightOfColumns')
              ->will($this->returnValue($columnsMarginsRight));

        $this->formatter->format($table, $this->createDocumentStub());
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
                array(0, 0, 0),
                array(0, 0, 0),
                100
            ),
            array(
                array(
                    array(10, 20, 30),
                    array(40, 10, 15),
                ),
                array(5, 10, 0),
                array(50, 20, 30),
                array(0, 0, 0),
                array(2, 2, 2),
                90
            ),
        );
    }
}