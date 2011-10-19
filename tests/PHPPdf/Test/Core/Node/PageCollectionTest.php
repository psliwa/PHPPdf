<?php

namespace PHPPdf\Test\Core\Node;

use PHPPdf\Core\Document;
use PHPPdf\Core\Node\Page;
use PHPPdf\Core\Node\PageCollection;

class PageCollectionTest extends \PHPPdf\PHPUnit\Framework\TestCase
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