<?php

use PHPPdf\Document;
use PHPPdf\Glyph\Page;
use PHPPdf\Glyph\PageCollection;
use PHPPdf\Glyph\DynamicPage;
use PHPPdf\Util\Point;
use PHPPdf\Util\Boundary;

class DynamicPageTest extends TestCase
{
    private $page;

    public function setUp()
    {
        $this->page = new DynamicPage();
    }

    /**
     * @test
     */
    public function creation()
    {
        $page = new DynamicPage();
        $this->assertNotEquals($page, $page->getPrototypePage());

        $singlePage = new Page();
        $page2 = new DynamicPage($singlePage);
        $this->assertEquals($singlePage, $page2->getPrototypePage());
    }

    /**
     * @test
     */
    public function dynamicPageCreation()
    {
        $this->assertEquals(0, count($this->page->getPages()));
        $this->page->createNextPage();
        $this->assertEquals(1, count($this->page->getPages()));
    }
    
    /**
     * @test
     */
    public function boundary()
    {
        $boundary = $this->page->getBoundary();

        $this->assertEquals(array(0, $this->page->getHeight()), $boundary->getFirstPoint()->toArray());
        $this->assertEquals(array($this->page->getWidth(), 0), $boundary->getDiagonalPoint()->toArray());
    }
    
    /**
     * @test
     */
    public function pageNumeration()
    {
        for($i=0; $i<2; $i++)
        {
            $this->page->createNextPage();
        }

        $pages = $this->page->getPages();
        $this->assertEquals(2, count($pages));

        $i=1;
        foreach($pages as $page)
        {
            $this->assertEquals($i, $page->getContext()->getPageNumber());
            $i++;
        }
    }
    
    /**
     * @test
     */
    public function serializePagePrototypeOnSerialization()
    {
        $stubPage = new Page();

        $dynamicPage = new DynamicPage($stubPage);
        $dynamicPageAfterUnserialization = unserialize(serialize($dynamicPage));
        $this->assertNotNull($dynamicPageAfterUnserialization->getPrototypePage());
    }
}