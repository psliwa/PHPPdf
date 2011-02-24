<?php

use PHPPdf\Glyph\PageContext,
    PHPPdf\Glyph\DynamicPage;

class PageContextTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function gettingNumberOfPages()
    {
        $numberOfPages = 5;
        $mock = $this->getMock('PHPPdf\Glyph\DynamicPage', array('getPages'));
        $mock->expects($this->atLeastOnce())
             ->method('getPages')
             ->will($this->returnValue(array_fill(0, $numberOfPages, 0)));

        $currentPageNumber = 3;
        $context = new PageContext($currentPageNumber, $mock);

        $this->assertEquals($numberOfPages, $context->getNumberOfPages());
        $this->assertEquals($currentPageNumber, $context->getPageNumber());
    }
}