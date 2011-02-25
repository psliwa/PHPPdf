<?php

use PHPPdf\Document;
use PHPPdf\Glyph\Page;
use PHPPdf\Glyph\PageCollection;
use PHPPdf\Glyph\DynamicPage;
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
    public function pageOverflow()
    {
        $prototype = $this->page->getPrototypePage();
        $container = $this->getContainerMock(array(0, $prototype->getHeight()), array($prototype->getWidth(), 0));
        $container2 = $this->getContainerMock(array(0, 0), array($prototype->getWidth(), -$prototype->getHeight()));

        $this->page->add($container);
        $this->page->add($container2);

        $tasks = $this->page->getDrawingTasks(new Document());

        foreach($tasks as $task)
        {
            $task->invoke();
        }

        $this->assertEquals(2, count($this->page->getPages()));
    }

    /**
     * @test
     */
    public function splitingChildren()
    {
        $prototype = $this->page->getPrototypePage();

        $container = $this->getContainerMock(array(0, $prototype->getHeight()), array($prototype->getWidth(), $prototype->getHeight()/2));
        $container2 = $this->getContainerMock(array(0, $prototype->getHeight()/2), array($prototype->getWidth(), -$prototype->getHeight()/2));

        $this->page->add($container);
        $this->page->add($container2);

        $tasks = $this->page->getDrawingTasks(new Document());

        foreach($tasks as $task)
        {
            $task->invoke();
        }

        $pages = $this->page->getPages();

        $this->assertEquals(2, count($pages));
        $this->assertEquals(2, count($pages[0]->getChildren()));
        $this->assertEquals(1, count($pages[1]->getChildren()));
    }

    private function getContainerMock($start, $end, array $methods = array())
    {
        $methods = array_merge(array('getBoundary', 'getHeight'), $methods);
        $mock = $this->getMock('PHPPdf\Glyph\Container', $methods);

        $boundary = new Boundary();
        $boundary->setNext($start[0], $start[1])
                 ->setNext($end[0], $start[1])
                 ->setNext($end[0], $end[1])
                 ->setNext($start[0], $end[1])
                 ->close();

        $mock->expects($this->atLeastOnce())
             ->method('getBoundary')
             ->will($this->returnValue($boundary));

        $mock->expects($this->any())
             ->method('getHeight')
             ->will($this->returnValue($start[1] - $end[1]));

        return $mock;
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
    public function multipleSplit()
    {
        $prototype = $this->page->getPrototypePage();
        $height = $prototype->getHeight();

        $mock = $this->getContainerMock(array(0, $height), array(100, -($height*3)));

        $this->page->add($mock);

        $tasks = $this->page->getDrawingTasks(new Document());

        foreach($tasks as $task)
        {
            $task->invoke();
        }

        $pages = $this->page->getPages();

        $this->assertEquals(4, count($pages));

        foreach($pages as $page)
        {
            $children = $page->getChildren();
            $this->assertEquals(1, count($children));
            
            $child = current($children);
            $this->assertEquals(array(0, $height), $child->getStartDrawingPoint());
            $this->assertEquals(array(100, 0), $child->getEndDrawingPoint());
        }
    }
    
    /**
     * @test
     */
    public function multipleSplitWithManyGlyphsPerPage()
    {
        $prototype = $this->page->getPrototypePage();
        $originalHeight = $height = $prototype->getHeight();
        
        $heightOfGlyph = (int) ($originalHeight*5/32);

        $mocks = array();
        for($i=0; $i<32; $i++, $height -= $heightOfGlyph)
        {
            $this->page->add($this->getContainerMock(array(0, $height), array(100, $height-$heightOfGlyph)));
        }

        $tasks = $this->page->getDrawingTasks(new Document());

        foreach($tasks as $task)
        {
            $task->invoke();
        }

        $pages = $this->page->getPages();
        $this->assertEquals(5, count($pages));
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
    public function pageBreak()
    {
        $prototype = $this->getMock('PHPPdf\Glyph\Page', array('copy'));
        $prototype->expects($this->exactly(2))
                  ->method('copy')
                  ->will($this->returnValue($prototype));

        $this->invokeMethod($this->page, 'setPrototypePage', array($prototype));

        $container = $this->getContainerMock(array(0, 700), array(40, 600), array('getPageBreak', 'split'));
        $container->expects($this->once())
                  ->method('getPageBreak')
                  ->will($this->returnValue(false));

        $this->page->add($container);

        $container = $this->getContainerMock(array(0, 600), array(0, 600), array('getPageBreak', 'split'));
        $container->expects($this->once())
                  ->method('getPageBreak')
                  ->will($this->returnValue(true));

        $this->page->add($container);

        $this->page->getDrawingTasks(new Document());
    }
}