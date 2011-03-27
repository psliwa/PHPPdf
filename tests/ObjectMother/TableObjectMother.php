<?php

class TableObjectMother
{
    private $test;

    public function  __construct(TestCase $test)
    {
        $this->test = $test;
    }

    public function getCellMockWithTranslateAndResizeExpectations($width, $newWidth, $translateX)
    {
        $boundary = $this->test->getMock('PHPPdf\Util\Boundary', array('pointTranslate'));

        $cell = $this->getCellMockWithResizeExpectations($width, $newWidth);

        $cell->expects($this->test->atLeastOnce())
             ->method('getBoundary')
             ->will($this->test->returnValue($boundary));

        if($translateX !== false)
        {
            $cell->expects($this->test->once())
                 ->method('translate')
                 ->with($translateX, 0);

        }

        $diff = $newWidth - $width;
        $boundary->expects($this->test->at(0))
                 ->method('pointTranslate')
                 ->with(1, $diff, 0)
                 ->will($this->test->returnValue($boundary));
        $boundary->expects($this->test->at(1))
                 ->method('pointTranslate')
                 ->with(2, $diff, 0)
                 ->will($this->test->returnValue($boundary));

        return $cell;
    }

    public function getCellMockWithResizeExpectations($width, $newWidth)
    {
        $cell = $this->test->getMock('PHPPdf\Glyph\Table\Cell', array('getWidth', 'getBoundary', 'setWidth', 'translate'));

        $cell->expects($this->test->atLeastOnce())
             ->method('getWidth')
             ->will($this->test->returnValue($width));
        $cell->expects($this->test->once())
             ->method('setWidth')
             ->with($newWidth);

        return $cell;
    }
}