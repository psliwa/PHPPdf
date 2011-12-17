<?php

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Formatter\PageBreakingFormatter,
    PHPPdf\Core\Node\DynamicPage,
    PHPPdf\Core\Boundary,
    PHPPdf\Core\Document,
    PHPPdf\Core\Node\Container;

class PageBreakingFormatterTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $page;
    private $formatter;

    public function setUp()
    {
        $this->page = new DynamicPage();
        $this->formatter = new PageBreakingFormatter();
    }

    /**
     * @test
     */
    public function pageOverflow()
    {
        $prototype = $this->page->getPrototypePage();
        $container = $this->getContainerStub(array(0, $prototype->getHeight()), array($prototype->getWidth(), 0));
        $container2 = $this->getContainerStub(array(0, 0), array($prototype->getWidth(), -$prototype->getHeight()));

        $this->page->add($container);
        $this->page->add($container2);

        $this->formatter->format($this->page, $this->createDocumentStub());

        $this->assertEquals(2, count($this->page->getPages()));
    }

    /**
     * @test
     */
    public function breakingChildren()
    {
        $prototype = $this->page->getPrototypePage();

        $container = $this->getContainerStub(array(0, $prototype->getHeight()), array($prototype->getWidth(), $prototype->getHeight()/2));
        $container2 = $this->getContainerStub(array(0, $prototype->getHeight()/2), array($prototype->getWidth(), -$prototype->getHeight()/2));

        $this->page->add($container);
        $this->page->add($container2);

        $this->formatter->format($this->page, $this->createDocumentStub());

        $pages = $this->page->getPages();

        $this->assertEquals(2, count($pages));
        $this->assertEquals(2, count($pages[0]->getChildren()));
        $this->assertEquals(1, count($pages[1]->getChildren()));
    }

    private function getContainerStub($start, $end, array $methods = array())
    {
        $stub = new Container();
        $stub->setHeight($start[1]-$end[1]);
        $stub->getBoundary()->setNext($start[0], $start[1])
                            ->setNext($end[0], $start[1])
                            ->setNext($end[0], $end[1])
                            ->setNext($start[0], $end[1])
                            ->close();

        $boundary = new Boundary();
        $boundary->setNext($start[0], $start[1])
                 ->setNext($end[0], $start[1])
                 ->setNext($end[0], $end[1])
                 ->setNext($start[0], $end[1])
                 ->close();

        return $stub;
    }

    private function getContainerMock($start, $end, array $methods = array())
    {
        $methods = array_merge(array('getBoundary', 'getHeight'), $methods);
        $mock = $this->getMock('PHPPdf\Core\Node\Container', $methods);

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
    public function multipleBreaking()
    {
        $prototype = $this->page->getPrototypePage();
        $height = $prototype->getHeight();

        $container = $this->getContainerStub(array(0, $height), array(100, -($height*3)));

        $this->page->add($container);

        $this->formatter->format($this->page, $this->createDocumentStub());

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
    public function multipleBreakingWithManyNodesPerPage()
    {
        $prototype = $this->page->getPrototypePage();
        $originalHeight = $height = $prototype->getHeight();

        $heightOfNode = (int) ($originalHeight*5/32);

        $mocks = array();
        for($i=0; $i<32; $i++, $height -= $heightOfNode)
        {
            $this->page->add($this->getContainerStub(array(0, $height), array(100, $height-$heightOfNode)));
        }

        $this->formatter->format($this->page, $this->createDocumentStub());

        $pages = $this->page->getPages();
        $this->assertEquals(5, count($pages));
    }


    /**
     * @test
     */
    public function pageShouldBeBreakIfBreakAttributeIsUsed()
    {
        $prototype = $this->getMock('PHPPdf\Core\Node\Page', array('copy'));
        $prototype->expects($this->exactly(2))
                  ->method('copy')
                  ->will($this->returnValue($prototype));

        $this->invokeMethod($this->page, 'setPrototypePage', array($prototype));

        $container = $this->getContainerMock(array(0, 700), array(40, 600), array('getAttribute', 'breakAt'));
        $container->expects($this->atLeastOnce())
                  ->method('getAttribute')
                  ->with('break')
                  ->will($this->returnValue(false));

        $this->page->add($container);

        $container = $this->getContainerMock(array(0, 600), array(0, 600), array('getAttribute', 'breakAt'));
        $container->expects($this->atLeastOnce())
                  ->method('getAttribute')
                  ->with('break')
                  ->will($this->returnValue(true));

        $this->page->add($container);

        $this->formatter->format($this->page, $this->createDocumentStub());
    }

    /**
     * @test
     *
     * @todo przerobić ten test, aby dotyczył nodeu który się podzielił na dwie strony, tylko że pomiędzy pierwszą częścią nodeu a końcem strony jest "luka" (np. tabela)
     */
    public function nextSiblingOfNotBreakableNodeMustBeDirectlyAfterThisNodeIfPageBreakOccurs()
    {
        $this->markTestIncomplete();

        $diagonalPoint = Point::getInstance(100, 10);

        $prototype = $this->getMock('PHPPdf\Core\Node\Page', array('copy', 'getHeight', 'getDiagonalPoint'));
        $this->page->setMarginBottom(10);
        $prototype->expects($this->exactly(1))
                  ->method('copy')
                  ->will($this->returnValue($prototype));

        $prototype->expects($this->atLeastOnce())
                  ->method('getHeight')
                  ->will($this->returnValue(100));

        $prototype->expects($this->atLeastOnce())
                  ->method('getDiagonalPoint')
                  ->will($this->returnValue($diagonalPoint));

        $this->invokeMethod($this->page, 'setPrototypePage', array($prototype));

        $notBrokenContainer = $this->getContainerMock(array(0, 100), array(50, 30), array('breakAt'));
        $notBrokenContainer->expects($this->never())
                             ->method('breakAt');

        $this->page->add($notBrokenContainer);

        $brokenContainer = $this->getContainerMock(array(0, 30), array(50, -10), array('breakAt'));
        $brokenContainer->expects($this->once())
                          ->method('breakAt')
                          ->will($this->returnValue(null));

        $this->page->add($brokenContainer);

        $nextSiblingOfBrokenContainer = $this->getContainerMock(array(0, -10), array(50, -20), array('breakAt'));
        $nextSiblingOfBrokenContainer->expects($this->never())
                                       ->method('breakAt');

        $this->page->add($nextSiblingOfBrokenContainer);

        $this->page->collectOrderedDrawingTasks($this->createDocumentStub());

        $this->assertEquals($brokenContainer->getDiagonalPoint()->getY(), $nextSiblingOfBrokenContainer->getFirstPoint()->getY());
    }
}