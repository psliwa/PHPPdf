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
        $mock = $this->getMock('PHPPdf\Node\DynamicPage', array('getNumberOfPages'));
        $mock->expects($this->atLeastOnce())
             ->method('getNumberOfPages')
             ->will($this->returnValue($numberOfPages));

        $currentPageNumber = 3;
        $context = new PageContext($currentPageNumber, $mock);

        $this->assertEquals($numberOfPages, $context->getNumberOfPages());
        $this->assertEquals($currentPageNumber, $context->getPageNumber());
    }
}