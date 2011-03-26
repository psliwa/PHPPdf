<?php

use PHPPdf\Formatter\TableFormatter;
use PHPPdf\Document;
use PHPPdf\Util\Boundary;

class TableFormatterTest extends PHPUnit_Framework_TestCase
{
    private $formatter;

    public function setUp()
    {
        $this->formatter = new TableFormatter();
    }

    /**
     * @test
     * @dataProvider cellsWidthProvider
     */
    public function equalizeCells(array $cellsWidthInRows, array $columnsWidths)
    {
        $rows = array();
        foreach($cellsWidthInRows as $widths)
        {
            $translate = 0;
            $cells = array();
            foreach($widths as $column => $width)
            {
                $columnWidth = $columnsWidths[$column];
                $cells[] = $this->getCellMockWithTranslateAndResizeExpectations($width, $columnWidth, $translate);
                $translate += $columnWidth;
            }

            $row = $this->getMock('PHPPdf\Glyph\Table\Row', array('getChildren'));
            $row->expects($this->atLeastOnce())
                ->method('getChildren')
                ->will($this->returnValue($cells));
            $rows[] = $row;
        }

        $table = $this->getMock('PHPPdf\Glyph\Table', array('getChildren', 'getWidthsOfColumns'));
        $table->expects($this->atLeastOnce())
              ->method('getChildren')
              ->will($this->returnValue($rows));
        $table->expects($this->atLeastOnce())
              ->method('getWidthsOfColumns')
              ->will($this->returnValue($columnsWidths));

        $this->formatter->format($table, new Document());
    }

    private function getCellMockWithTranslateAndResizeExpectations($width, $newWidth, $translateX)
    {
        $boundary = $this->getMock('PHPPdf\Util\Boundary', array('pointTranslate'));
        $cell = $this->getMock('PHPPdf\Glyph\Table\Cell', array('getWidth', 'getBoundary', 'setWidth', 'translate'));

        $cell->expects($this->atLeastOnce())
             ->method('getWidth')
             ->will($this->returnValue($width));
        $cell->expects($this->once())
             ->method('setWidth')
             ->with($newWidth);
        $cell->expects($this->atLeastOnce())
             ->method('getBoundary')
             ->will($this->returnValue($boundary));

        $cell->expects($this->once())
             ->method('translate')
             ->with($translateX, 0);
        $diff = $newWidth - $width;
        $boundary->expects($this->at(0))
                 ->method('pointTranslate')
                 ->with(1, $diff, 0)
                 ->will($this->returnValue($boundary));
        $boundary->expects($this->at(1))
                 ->method('pointTranslate')
                 ->with(2, $diff, 0)
                 ->will($this->returnValue($boundary));

        return $cell;
    }

    public function cellsWidthProvider()
    {
        return array(
            array(
                array(
                    array(10, 20, 30),
                    array(40, 10, 15),
                ),
                array(50, 20, 30),
            ),
        );
    }
}