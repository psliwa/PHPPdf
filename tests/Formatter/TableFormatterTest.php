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
     */
    public function steadyCellWidth()
    {
        $table = $this->getMock('PHPPdf\Glyph\Table', array('getWidth', 'getChildren'));
        $table->expects($this->exactly(2))
              ->method('getWidth')
              ->will($this->returnValue(400));

        $row = $this->getMock('PHPPdf\Glyph\Table\Row', array('getParent', 'getChildren'));
        $row->expects($this->atLeastOnce())
            ->method('getParent')
            ->will($this->returnValue($table));

        $width = 25;
        for($i=0; $i<3; $i++)
        {
            $cells[] = $this->getCellMock(0, 300, 0, 20, $width);
        }

        $table->expects($this->once())
              ->method('getChildren')
              ->will($this->returnValue(array($row)));

        $cellWithWidth = $this->getCellMock(0, 300, 300, 20, 325);
        $cells[] = $cellWithWidth;

        $row->expects($this->exactly(2))
            ->method('getChildren')
            ->will($this->returnValue($cells));

        $this->formatter->format($table, new Document());

        $prevCellBoundary = null;
        foreach($cells as $cell)
        {
            $boundary = $cell->getBoundary();
            if($prevCellBoundary)
            {
                $this->assertEquals($prevCellBoundary[1], $boundary[0]);
            }
            $prevCellBoundary = $boundary;
        }
    }

    private function getCellMock($x, $y, $width, $height, $setWidthConstraint)
    {
        $cell = $this->getMock('PHPPdf\Glyph\Table\Cell', array('setWidth', 'getWidth', 'getBoundary', 'setHeight', 'getHeight'));

        $cell->expects($this->once())
             ->method('setWidth')
             ->with($setWidthConstraint)
             ->will($this->returnValue($cell));

        $cell->expects($this->any())
             ->method('getWidth')
             ->will($this->returnValue($width));

        $cell->expects($this->atLeastOnce())
             ->method('getHeight')
             ->will($this->returnValue($height));

        $boundary = new Boundary();
        $boundary->setNext($x, $y)
                 ->setNext($x+$width, $y)
                 ->setNext($x+$width, $y-$height)
                 ->setNext($x, $y-$height)
                 ->close();
        $cell->expects($this->any())
             ->method('getBoundary')
             ->will($this->returnValue($boundary));

        return $cell;
    }
}