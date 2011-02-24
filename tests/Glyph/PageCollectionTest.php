<?php

use PHPPdf\Document;
use PHPPdf\Glyph\Page;
use PHPPdf\Glyph\PageCollection;

class PageCollectionTest extends PHPUnit_Framework_TestCase
{
    private $pageCollection;

    public function setUp()
    {
        $this->pageCollection = new PageCollection();
    }
    
    /**
     * @test
     */
    public function addingPages()
    {
        $page1 = $this->getMockPage();
        $page2 = $this->getMockPage();

        $this->pageCollection->add($page1)->add($page2);

        $children = $this->pageCollection->getChildren();
        $this->assertEquals(2, count($children));
        $this->assertEquals($page1, $children[0]);
        $this->assertEquals($page2, $children[1]);

        $this->assertNull($page1->getParent());
    }

    private function getMockPage()
    {
        return new Page();
    }
}