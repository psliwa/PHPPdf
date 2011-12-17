<?php

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Formatter\RowDimensionFormatter,
    PHPPdf\Core\Boundary,
    PHPPdf\Core\Document;

class RowDimensionFormatterTest extends \PHPPdf\PHPUnit\Framework\TestCase
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

        $this->formatter->format($row, $this->createDocumentStub());
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
        $boundary = $this->getMock('PHPPdf\Core\Boundary', array('pointTranslate'));
        $boundary->expects($this->at(0))
                 ->method('pointTranslate')
                 ->with(2, 0, $enlargeBy)
                 ->will($this->returnValue($boundary));
        $boundary->expects($this->at(1))
                 ->method('pointTranslate')
                 ->with(3, 0, $enlargeBy);

        return $boundary;
    }

    private function getRowMockWithHeightAsserts($boundary, $oldHeight, $maxHeightOfCells, $expectedNewHeight = null)
    {
        $row = $this->getMock('PHPPdf\Core\Node\Table\Row', array('getBoundary', 'getHeight', 'setHeight', 'getMaxHeightOfCells', 'getChildren', 'getMarginsBottomOfCells', 'getMarginsTopOfCells'));

        $expectedNewHeight = $expectedNewHeight === null ? $maxHeightOfCells : $expectedNewHeight;

        $row->expects($this->atLeastOnce())
            ->method('getBoundary')
            ->will($this->returnValue($boundary));
        $row->expects($this->atLeastOnce())
            ->method('getHeight')
            ->will($this->returnValue($oldHeight));
        $row->expects($this->once())
            ->method('setHeight')
            ->with($expectedNewHeight);
        $row->expects($this->atLeastOnce())
            ->method('getMaxHeightOfCells')
            ->will($this->returnValue($maxHeightOfCells));

        return $row;
    }

    /**
     * @test
     * @dataProvider marginsDataProvider
     */
    public function enlargeCellsToRowHeight($rowHeight, array $cellHeights, $marginTop, $marginBottom)
    {
        $verticalMargins = $marginTop + $marginBottom;

        $cells = array();

        foreach($cellHeights as $height)
        {
            $boundary = $this->getBoundaryMockWithEnlargeAsserts($rowHeight - $height);

            $cell = $this->getMock('PHPPdf\Core\Node\Table\Cell', array('getHeight', 'setHeight', 'getBoundary'));

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

        $boundary = $this->getBoundaryMockWithEnlargeAsserts($verticalMargins);
        $row = $this->getRowMockWithHeightAsserts($boundary, $rowHeight, $rowHeight, $rowHeight + $verticalMargins);
        $row->expects($this->atLeastOnce())
            ->method('getMarginsBottomOfCells')
            ->will($this->returnValue($marginBottom));
        $row->expects($this->atLeastOnce())
            ->method('getMarginsTopOfCells')
            ->will($this->returnValue($marginTop));

        $row->expects($this->atLeastOnce())
            ->method('getChildren')
            ->will($this->returnValue($cells));

        $this->formatter->format($row, $this->createDocumentStub());
    }

    public function marginsDataProvider()
    {
        return array(
            array(
                100, array(30, 50), 10, 12
            ),
            array(
                100, array(30, 50), 0, 0
            ),
        );
    }
}