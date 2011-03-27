<?php

use PHPPdf\Formatter\TableColumnWidthFormatter,
    PHPPdf\Document;

class TableColumnWidthFormatterTest extends TestCase
{
    private $formatter;
    private $objectMother;

    protected function init()
    {
        $this->objectMother = new TableObjectMother($this);
    }

    public function setUp()
    {
        $this->formatter = new TableColumnWidthFormatter();
    }

    /**
     * @test
     * @dataProvider columnsDataProvider
     */
    public function spreadEventlyColumnsWidth(array $columnsWidths, $numberOfRows, $tableWidth)
    {
        $table = $this->getMock('PHPPdf\Glyph\Table', array('getWidthsOfColumns', 'getChildren', 'getWidth', 'getNumberOfColumns'));
        $totalColumnsWidth = array_sum($columnsWidths);
        $enlargeColumnWidth = ($tableWidth - $totalColumnsWidth)/count($columnsWidths);

        $rows = array();
        for($i=0; $i<$numberOfRows; $i++)
        {
            $cells = array();
            foreach($columnsWidths as $width)
            {
                $cells[] = $this->objectMother->getCellMockWithResizeExpectations($width, $width+$enlargeColumnWidth, false);
            }

            $row = $this->getMock('PHPPdf\Glyph\Table\Row', array('getChildren'));
            $row->expects($this->atLeastOnce())
                ->method('getChildren')
                ->will($this->returnValue($cells));

            $rows[] = $row;
        }
        
        $table->expects($this->atLeastOnce())
              ->method('getChildren')
              ->will($this->returnValue($rows));

        $table->expects($this->atLeastOnce())
              ->method('getWidth')
              ->will($this->returnValue($tableWidth));

        $table->expects($this->atLeastOnce())
              ->method('getWidthsOfColumns')
              ->will($this->returnValue($columnsWidths));

        $table->expects($this->atLeastOnce())
              ->method('getNumberOfColumns')
              ->will($this->returnValue(count($columnsWidths)));

        $this->formatter->format($table, new Document());
    }

    public function columnsDataProvider()
    {
        return array(
            array(
                array(10, 20, 15),
                5,
                50,
            ),
        );
    }
}