<?php

namespace PHPPdf\Test\Node;

use PHPPdf\Node\PageContext,
    PHPPdf\Node\DynamicPage;

class PageContextTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function gettingNumberOfPages()
    {
        $numberOfPages = 5;
        $mock = $this->getMock('PHPPdf\Node\DynamicPage', array('getPages'));
        $mock->expects($this->atLeastOnce())
             ->method('getPages')
             ->will($this->returnValue(array_fill(0, $numberOfPages, 0)));

        $currentPageNumber = 3;
        $context = new PageContext($currentPageNumber, $mock);

        $this->assertEquals($numberOfPages, $context->getNumberOfPages());
        $this->assertEquals($currentPageNumber, $context->getPageNumber());
    }
}