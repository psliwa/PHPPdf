<?php

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Formatter\TableColumnFormatter,
    PHPPdf\ObjectMother\TableObjectMother,
    PHPPdf\Core\Document;

class TableColumnFormatterTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $formatter;
    private $objectMother;

    protected function init()
    {
        $this->objectMother = new TableObjectMother($this);
    }

    public function setUp()
    {
        $this->formatter = new TableColumnFormatter();
    }

    /**
     * @test
     * @dataProvider columnsDataProvider
     */
    public function spreadEventlyColumnsWidth(array $cellsInRowsWidths, array $columnsWidths, $tableWidth)
    {
        $table = $this->getMock('PHPPdf\Core\Node\Table', array('reduceColumnsWidthsByMargins', 'getWidthsOfColumns', 'getChildren', 'getWidth', 'getNumberOfColumns', 'getMarginsLeftOfColumns', 'getMarginsRightOfColumns', 'convertRelativeWidthsOfColumns'));
        $totalColumnsWidth = array_sum($columnsWidths);
        $numberOfColumns = count($columnsWidths);
        $enlargeColumnWidth = ($tableWidth - $totalColumnsWidth)/$numberOfColumns;

        $rows = array();
        foreach($cellsInRowsWidths as $cellsWidths)
        {
            $cells = array();
            foreach($cellsWidths as $column => $width)
            {
                $cell = $this->objectMother->getCellMockWithResizeExpectations($width, $columnsWidths[$column] + $enlargeColumnWidth, false);
                $cell->expects($this->atLeastOnce())
                     ->method('getNumberOfColumn')
                     ->will($this->returnValue($column));
                $cells[] = $cell;
            }

            $row = $this->getMock('PHPPdf\Core\Node\Table\Row', array('getChildren'));
            $row->expects($this->atLeastOnce())
                ->method('getChildren')
                ->will($this->returnValue($cells));

            $rows[] = $row;
        }

        $table->expects($this->once())
              ->id('convertColumns')
              ->method('convertRelativeWidthsOfColumns');

        $table->expects($this->once())
              ->id('reduceColumns')
              ->after('convertColumns')
              ->method('reduceColumnsWidthsByMargins');

        $table->expects($this->atLeastOnce())
              ->method('getChildren')
              ->after('reduceColumns')
              ->will($this->returnValue($rows));

        $table->expects($this->atLeastOnce())
              ->method('getWidth')
              ->after('reduceColumns')
              ->will($this->returnValue($tableWidth));

        $table->expects($this->atLeastOnce())
              ->method('getWidthsOfColumns')
              ->after('reduceColumns')
              ->will($this->returnValue($columnsWidths));

        $table->expects($this->atLeastOnce())
              ->method('getNumberOfColumns')
              ->after('reduceColumns')
              ->will($this->returnValue(count($columnsWidths)));

        $margins = array_fill(0, $numberOfColumns, 0);
        $table->expects($this->atLeastOnce())
              ->method('getMarginsLeftOfColumns')
              ->after('reduceColumns')
              ->will($this->returnValue($margins));

        $table->expects($this->atLeastOnce())
              ->method('getMarginsRightOfColumns')
              ->after('reduceColumns')
              ->will($this->returnValue($margins));

        $this->formatter->format($table, $this->createDocumentStub());
    }

    public function columnsDataProvider()
    {
        return array(
            array(
                array(
                    array(10, 20, 15),
                    array(5, 10, 10),
                ),
                array(10, 20, 15),
                5,
                50,
            ),
        );
    }
}