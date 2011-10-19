<?php

namespace PHPPdf\ObjectMother;

class TableObjectMother
{
    private $test;

    public function  __construct(\PHPUnit_Framework_TestCase $test)
    {
        $this->test = $test;
    }

    public function getCellMockWithTranslateAndResizeExpectations($width, $newWidth, $translateX)
    {
        $boundary = $this->test->getMock('PHPPdf\Core\Boundary', array('pointTranslate', 'getMinWidth'));

        $cell = $this->getCellMockWithResizeExpectations($width, $newWidth);

        if($translateX !== false)
        {
            $cell->expects($this->test->once())
                 ->method('translate')
                 ->with($translateX, 0);
        }

        return $cell;
    }

    public function getCellMockWithResizeExpectations($width, $newWidth, $invokeResizeMethod = true)
    {
        $cell = $this->test->getMock('PHPPdf\Core\Node\Table\Cell', array('getWidth', 'getBoundary', 'setWidth', 'translate', 'getNumberOfColumn', 'resize'));

        $cell->expects($this->test->any())
             ->method('getWidth')
             ->will($this->test->returnValue($width));
        $cell->expects($this->test->once())
             ->method('setWidth')
             ->with($newWidth);

        if($invokeResizeMethod)
        {
            $cell->expects($this->test->once())
                 ->method('resize')
                 ->with($newWidth - $width);
        }
        else
        {
            $cell->expects($this->test->never())
                 ->method('resize');
        }

        return $cell;
    }
}