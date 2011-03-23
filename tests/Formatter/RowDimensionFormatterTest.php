<?php

use PHPPdf\Formatter\RowDimensionFormatter,
    PHPPdf\Util\Boundary,
    PHPPdf\Document;

class RowDimensionFormatterTest extends TestCase
{
    private $formatter;

    public function setUp()
    {
        $this->formatter = new RowDimensionFormatter();
    }

    /**
     * @test
     * @dataProvider heightProvider
     */
    public function changeRowsHeightIfMaxCellHeigtIsGreater($oldHeight, $height)
    {
        $diff = $height - $oldHeight;

        $boundary = $this->getBoundaryMockWithEnlargeAsserts($diff);

        $row = $this->getRowMockWithHeightAsserts($boundary, $oldHeight, $height);

        $this->formatter->format($row, new Document());
    }

    public function heightProvider()
    {
        return array(
            array(100, 150),
            array(100, 80),
        );
    }

    private function getBoundaryMockWithEnlargeAsserts($enlargeBy)
    {
        $boundary = $this->getMock('PHPPdf\Util\Boundary', array('pointTranslate'));
        $boundary->expects($this->at(0))
                 ->method('pointTranslate')
                 ->with(2, 0, $enlargeBy)
                 ->will($this->returnValue($boundary));
        $boundary->expects($this->at(1))
                 ->method('pointTranslate')
                 ->with(3, 0, $enlargeBy);

        return $boundary;
    }

    private function getRowMockWithHeightAsserts($boundary, $oldHeight, $maxHeightOfCells)
    {
        $row = $this->getMock('PHPPdf\Glyph\Table\Row', array('getBoundary', 'getHeight', 'setHeight', 'getMaxHeightOfCells', 'getChildren'));

        $row->expects($this->atLeastOnce())
            ->method('getBoundary')
            ->will($this->returnValue($boundary));
        $row->expects($this->atLeastOnce())
            ->method('getHeight')
            ->will($this->returnValue($oldHeight));
        $row->expects($this->once())
            ->method('setHeight')
            ->with($maxHeightOfCells);
        $row->expects($this->atLeastOnce())
            ->method('getMaxHeightOfCells')
            ->will($this->returnValue($maxHeightOfCells));

        return $row;
    }

    /**
     * @test
     */
    public function enlargeCellsToRowHeight()
    {
        $rowHeight = 100;
        $heights = array(30, 50);
        $cells = array();

        foreach($heights as $height)
        {
            $boundary = $this->getBoundaryMockWithEnlargeAsserts($rowHeight - $height);

            $cell = $this->getMock('PHPPdf\Glyph\Table\Cell', array('getHeight', 'setHeight', 'getBoundary'));

            $cell->expects($this->atLeastOnce())
                 ->method('getBoundary')
                 ->will($this->returnValue($boundary));
            $cell->expects($this->atLeastOnce())
                 ->method('getHeight')
                 ->will($this->returnValue($height));
            $cell->expects($this->once())
                 ->method('setHeight')
                 ->with($rowHeight);

            $cells[] = $cell;
        }

        $boundary = $this->getBoundaryMockWithEnlargeAsserts(0);
        $row = $this->getRowMockWithHeightAsserts($boundary, $rowHeight, $rowHeight);

        $row->expects($this->atLeastOnce())
            ->method('getChildren')
            ->will($this->returnValue($cells));

        $this->formatter->format($row, new Document());
    }
}