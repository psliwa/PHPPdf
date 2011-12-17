<?php

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Formatter\TextPositionFormatter,
    PHPPdf\Core\Point,
    PHPPdf\Core\Document;

class TextPositionFormatterTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    const TEXT_LINE_HEIGHT = 14;
    
    private $formatter;

    public function setUp()
    {
        $this->formatter = new TextPositionFormatter();
    }

    /**
     * @test
     */
    public function addPointsToBoundaryAccordingToLineSizes()
    {
        $mock = $this->getTextMock(array(50, 100), array(0, 700));

        $this->formatter->format($mock, $this->createDocumentStub());
    }

    private function getTextMock($lineSizes, $parentFirstPoint, $firstXCoord = null)
    {
        $parentMock = $this->getMock('\PHPPdf\Core\Node\Node', array('getStartDrawingPoint'));
        $parentMock->expects($this->once())
                   ->method('getStartDrawingPoint')
                   ->will($this->returnValue(array(0, 700)));

        $mock = $this->getMock('\PHPPdf\Core\Node\Text', array(
            'getParent',
            'getLineHeightRecursively',
            'getLineSizes',
            'getStartDrawingPoint',
            'getBoundary',
        ));

        $mock->expects($this->atLeastOnce())
             ->method('getParent')
             ->will($this->returnValue($parentMock));

        $boundaryMock = $this->getMock('\PHPPdf\Core\Boundary', array(
            'getFirstPoint',
            'setNext',
            'close',
        ));

        $firstXCoord = $firstXCoord ? $firstXCoord : $parentFirstPoint[0];
        $boundaryMock->expects($this->atLeastOnce())
                     ->method('getFirstPoint')
                     ->will($this->returnValue(Point::getInstance($firstXCoord, $parentFirstPoint[1])));

        $this->addBoundaryPointsAsserts($boundaryMock, $lineSizes, $parentFirstPoint[1]);

        $mock->expects($this->atLeastOnce())
             ->method('getBoundary')
             ->will($this->returnValue($boundaryMock));

        $mock->expects($this->atLeastOnce())
             ->method('getBoundary')
             ->will($this->returnValue($boundaryMock));

        $mock->expects($this->once())
             ->method('getLineHeightRecursively')
             ->will($this->returnValue(self::TEXT_LINE_HEIGHT));

        $mock->expects($this->once())
             ->method('getLineSizes')
             ->will($this->returnValue($lineSizes));

        return $mock;
    }

    private function addBoundaryPointsAsserts($boundaryMock, $lineSizes, $firstYCoord)
    {
        $at = 1;
        foreach($lineSizes as $i => $size)
        {
            $yCoord = $firstYCoord - self::TEXT_LINE_HEIGHT*$i;
            $boundaryMock->expects($this->at($at++))
                         ->method('setNext')
                         ->with($size, $yCoord);

            if(isset($lineSizes[$i+1]))
            {
                $boundaryMock->expects($this->at($at++))
                             ->method('setNext')
                             ->with($size, $yCoord - self::TEXT_LINE_HEIGHT);
            }
        }

        $boundaryMock->expects($this->once())
                     ->method('close');
    }
}