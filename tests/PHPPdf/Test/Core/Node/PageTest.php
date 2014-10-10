<?php

namespace PHPPdf\Test\Core\Node;

use PHPPdf\Core\Engine\ZF\Engine;

use PHPPdf\Core\DrawingTaskHeap;

use PHPPdf\Core\Node\Node;
use PHPPdf\Core\DrawingTask;
use PHPPdf\Core\Document;
use PHPPdf\Core\Node\Page;
use PHPPdf\Core\Node\PageContext;
use PHPPdf\Core\Node\DynamicPage;
use PHPPdf\Core\Node\Container;
use PHPPdf\Core\Boundary;

class PageTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $page;
    private $document;

    public function setUp()
    {
        $this->page = new Page();
        $this->document = new Document(new Engine());
    }

    /**
     * @test
     */
    public function drawing()
    {
        $tasks = new DrawingTaskHeap();
        $this->page->collectOrderedDrawingTasks($this->document, $tasks);

        foreach($tasks as $task)
        {
            $task->invoke();
        }
    }

    /**
     * @test
     * @expectedException PHPPdf\Core\Exception\DrawingException
     */
    public function failureDrawing()
    {
        $child = $this->getMock('\PHPPdf\Core\Node\Node', array('doDraw'));
        $child->expects($this->any())
              ->method('doDraw')
              ->will($this->throwException(new \Exception('exception')));
         
        $this->page->add($child);
        
        $tasks = new DrawingTaskHeap();
        $this->page->collectOrderedDrawingTasks($this->document, $tasks);

        foreach($tasks as $task)
        {
            $task->invoke();
        }
    }

    /**
     * @test
     */
    public function attributes()
    {
        $this->assertEquals(595, $this->page->getWidth());

        $this->page->setPageSize('100:200');
        $this->assertEquals(100, $this->page->getWidth());
        $this->assertEquals(200, $this->page->getHeight());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function invalidPageSize()
    {
        $this->page->setPageSize('100');
    }

    /**
     * @test
     */
    public function getStartingPoint()
    {
        list($x, $y) = $this->page->getStartDrawingPoint();
        $this->assertEquals($this->page->getHeight(), $y);
        $this->assertEquals(0, $x);
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
    public function innerMargins()
    {
        $height = $this->page->getHeight();
        $width = $this->page->getWidth();

        $firstPoint = $this->page->getFirstPoint();
        $diagonalPoint = $this->page->getDiagonalPoint();

        $verticalMargin = 40;
        $horizontalMargin = 20;
        
        $originalVerticalMargin = 33;
        $originalHorizontalMargin = 22;
        
        $unitConverter = $this->getMock('PHPPdf\Core\UnitConverter');
        $this->page->setUnitConverter($unitConverter);
        
        foreach(array(0, 2) as $i)
        {
            $unitConverter->expects($this->at($i))
                          ->method('convertUnit')
                          ->with($originalVerticalMargin)
                          ->will($this->returnValue($verticalMargin));
        }
        
        foreach(array(1, 3) as $i)
        {
            $unitConverter->expects($this->at($i))
                          ->method('convertUnit')
                          ->with($originalHorizontalMargin)
                          ->will($this->returnValue($horizontalMargin));
        }
        
        $this->page->setMargin($originalVerticalMargin, $originalHorizontalMargin);

        $this->assertEquals($height - 2*$verticalMargin, $this->page->getHeight());
        $this->assertEquals($width - 2*$horizontalMargin, $this->page->getWidth());

        $this->assertEquals($firstPoint->translate(20, 40), $this->page->getFirstPoint());
        $this->assertEquals($diagonalPoint->translate(-20, -40), $this->page->getDiagonalPoint());
    }
    
    /**
     * @test
     */
    public function addingFooter()
    {
        $boundary = new Boundary();

        $footerHeight = 25;
        $mock = $this->createFooterOrHeaderMock($boundary, $footerHeight);

        $this->page->setMargin(20);
        $pageBoundary = clone $this->page->getBoundary();
        
        $this->page->setFooter($mock);

        $this->assertEquals($pageBoundary[3]->translate(0, -$footerHeight), $boundary[0]);
        $this->assertEquals($pageBoundary[2]->translate(0, -$footerHeight), $boundary[1]);
        $this->assertEquals($pageBoundary[2], $boundary[2]);
        $this->assertEquals($pageBoundary[3], $boundary[3]);

        $this->assertTrue($this->page->getPlaceholder('footer') === $mock);
    }

    private function createFooterOrHeaderMock(Boundary $boundary, $height = null)
    {
        $mock = $this->getMock('PHPPdf\Core\Node\Container', array('getBoundary', 'getHeight', 'setStaticSize'));
        $mock->expects($this->atLeastOnce())
             ->method('getBoundary')
             ->will($this->returnValue($boundary));

        $mock->expects($this->once())
             ->method('setStaticSize')
             ->with($this->equalTo(true))
             ->will($this->returnValue($mock));

        if($height !== null)
        {
            $mock->expects($this->atLeastOnce())
                 ->method('getHeight')
                 ->will($this->returnValue($height));
        }

        return $mock;
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function exceptionIfFootersHeightIsNull()
    {
        $footer = new Container();

        $this->page->setFooter($footer);
    }
    
    /**
     * @test
     */
    public function addingHeader()
    {
        $boundary = new Boundary();

        $headerHeight = 25;
        $mock = $this->createFooterOrHeaderMock($boundary, 25);
        $this->page->setMargin(20);
        $pageBoundary = clone $this->page->getBoundary();
        $this->page->setHeader($mock);

        $realHeight = $this->page->getRealHeight();
        $this->assertEquals($pageBoundary[0], $boundary[0]);
        $this->assertEquals($pageBoundary[1], $boundary[1]);
        $this->assertEquals($pageBoundary[1]->translate(0, $headerHeight), $boundary[2]);
        $this->assertEquals($pageBoundary[0]->translate(0, $headerHeight), $boundary[3]);

        $this->assertTrue($this->page->getPlaceholder('header') === $mock);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function exceptionIfHeadersHeightIsNull()
    {
        $header = new Container();

        $this->page->setHeader($header);
    }

    /**
     * @test
     * @expectedException \LogicException
     */
    public function exceptionIfPageContextNotFound()
    {
        $this->page->getContext();
    }

    /**
     * @test
     */
    public function gettingContext()
    {
        $dynamicPage = new DynamicPage();
        $context = new PageContext(1, $dynamicPage);
        $this->page->setContext($context);

        $this->assertEquals($context, $this->page->getContext());
    }

    /**
     * @test
     */
    public function copyOfPageCloneAlsoBoundary()
    {
        $copy = $this->page->copy();

        $this->assertTrue($copy->getBoundary() !== null && $this->page->getBoundary() !== null);
        $this->assertFalse($copy->getBoundary() === $this->page->getBoundary());

        $copyBoundary = $copy->getBoundary();
        $boundary = $this->page->getBoundary();
        foreach($copyBoundary as $i => $point)
        {
            $this->assertTrue($point === $boundary[$i]);
        }
    }

    /**
     * @test
     * @dataProvider booleanProvider
     */
    public function drawingTasksFromPlaceholderAreInResultOfGetDrawingTasksIfPrepareTemplateMethodHasNotBeenInvoked($invoke)
    {
        $tasks = array(new DrawingTask(function(){}), new DrawingTask(function(){}));
        
        $header = $this->getMock('PHPPdf\Core\Node\Container', array('format', 'getHeight', 'collectOrderedDrawingTasks'));
        $header->expects($this->once())
               ->method('format');
        $header->expects($this->atLeastOnce())
               ->method('getHeight')
               ->will($this->returnValue(10));
        $header->expects($this->once())
               ->method('collectOrderedDrawingTasks')
               ->will($this->returnValue($tasks));
        
        $this->page->setHeader($header);
        
        if($invoke)
        {
            $this->page->prepareTemplate($this->document);
        }
        
        $actualTasks = new DrawingTaskHeap();
        $this->page->collectOrderedDrawingTasks($this->document, $actualTasks);
        
        foreach($actualTasks as $task)
        {
            $this->assertEquals(!$invoke, in_array($task, $tasks));
        }
    }
    
    public function booleanProvider()
    {
        return array(
            array(false),
            array(true),
        );
    }

    /**
     * @test
     */
    public function formatMethodDosntInvokePlaceholdersFormatMethod()
    {
        $header = $this->getPlaceholderMockWithNeverFormatMethodInvocation();
        $footer = $this->getPlaceholderMockWithNeverFormatMethodInvocation();
        $watermark = $this->getPlaceholderMockWithNeverFormatMethodInvocation();

        $this->page->setHeader($header);
        $this->page->setHeader($footer);
        $this->page->setWatermark($watermark);

        $this->page->format($this->createDocumentStub());
    }

    private function getPlaceholderMockWithNeverFormatMethodInvocation()
    {
        $header = $this->getMock('PHPPdf\Core\Node\Container', array('format', 'getHeight'));
        $header->expects($this->never())
               ->method('format');
        $header->expects($this->any())
               ->method('getHeight')
               ->will($this->returnValue(10));

        return $header;
    }
    
    /**
     * @test
     */
    public function pageCopingDosntCreateGraphicContextIfNotExists()
    {
        $this->assertNull($this->readAttribute($this->page, 'graphicsContext'));
        
        $copyPage = $this->page->copy();
        
        $this->assertNull($this->readAttribute($this->page, 'graphicsContext'));
        $this->assertNull($this->readAttribute($copyPage, 'graphicsContext'));
    }
    
    /**
     * @test
     * @dataProvider pageSizesProvider
     */
    public function resizeBoundaryWhenPageSizeIsSet($width, $height, array $margins)
    {        
        foreach($margins as $name => $value)
        {
            $this->page->setAttribute($name, $value);
        }

        $this->page->setAttribute('page-size', sprintf('%d:%d', $width, $height));
        
        $expectedTopLeftPoint = array($this->page->getMarginLeft(), $height - $this->page->getMarginTop());
        $expectedBottomRightPoint = array($width - $this->page->getMarginRight(), $this->page->getMarginBottom());

        $this->assertEquals($expectedTopLeftPoint, $this->page->getFirstPoint()->toArray());
        $this->assertEquals($expectedBottomRightPoint, $this->page->getDiagonalPoint()->toArray());
    }
    
    public function pageSizesProvider()
    {
        return array(
            array(100, 50, array('margin' => 0)),
            array(77, 55, array('margin' => '2 3 4 5')),
        );
    }
    
    /**
     * @test
     * @dataProvider humanReadablePageSizeProvider
     */
    public function allowHumanReadablePageSizeAttribute($pageSize, $expectedSize, $landscape)
    {
        $this->page->setAttribute('page-size', $pageSize);
        
	if ($landscape) {
		list($expectedHeight, $expectedWidth) = explode(':', $expectedSize);
	} else {
		list($expectedWidth, $expectedHeight) = explode(':', $expectedSize);
	}
        
        $this->assertEquals($expectedWidth, $this->page->getWidth());
        $this->assertEquals($expectedHeight, $this->page->getHeight());
    }
    
    public function humanReadablePageSizeProvider()
    {
        return array(
            array('4a0', Page::SIZE_4A0, false),
            array('4A0', Page::SIZE_4A0, false),
            array('4a0-landscape', Page::SIZE_4A0, true),
            array('4a0_landscape', Page::SIZE_4A0, true),
            array('2a0', Page::SIZE_2A0, false),
            array('2A0', Page::SIZE_2A0, false),
            array('2a0-landscape', Page::SIZE_2A0, true),
            array('2a0_landscape', Page::SIZE_2A0, true),
            array('a0', Page::SIZE_A0, false),
            array('A0', Page::SIZE_A0, false),
            array('a0-landscape', Page::SIZE_A0, true),
            array('a0_landscape', Page::SIZE_A0, true),
            array('a1', Page::SIZE_A1, false),
            array('A1', Page::SIZE_A1, false),
            array('a1-landscape', Page::SIZE_A1, true),
            array('a1_landscape', Page::SIZE_A1, true),
            array('a2', Page::SIZE_A2, false),
            array('A2', Page::SIZE_A2, false),
            array('a2-landscape', Page::SIZE_A2, true),
            array('a2_landscape', Page::SIZE_A2, true),
            array('a3', Page::SIZE_A3, false),
            array('A3', Page::SIZE_A3, false),
            array('a3-landscape', Page::SIZE_A3, true),
            array('a3_landscape', Page::SIZE_A3, true),
            array('a4', Page::SIZE_A4, false),
            array('A4', Page::SIZE_A4, false),
            array('a4-landscape', Page::SIZE_A4, true),
            array('a4_landscape', Page::SIZE_A4, true),
            array('a5', Page::SIZE_A5, false),
            array('A5', Page::SIZE_A5, false),
            array('a5-landscape', Page::SIZE_A5, true),
            array('a5_landscape', Page::SIZE_A5, true),
            array('a6', Page::SIZE_A6, false),
            array('A6', Page::SIZE_A6, false),
            array('a6-landscape', Page::SIZE_A6, true),
            array('a6_landscape', Page::SIZE_A6, true),
            array('a7', Page::SIZE_A7, false),
            array('A7', Page::SIZE_A7, false),
            array('a7-landscape', Page::SIZE_A7, true),
            array('a7_landscape', Page::SIZE_A7, true),
            array('a8', Page::SIZE_A8, false),
            array('A8', Page::SIZE_A8, false),
            array('a8-landscape', Page::SIZE_A8, true),
            array('a8_landscape', Page::SIZE_A8, true),
            array('a9', Page::SIZE_A9, false),
            array('A9', Page::SIZE_A9, false),
            array('a9-landscape', Page::SIZE_A9, true),
            array('a9_landscape', Page::SIZE_A9, true),
            array('a10', Page::SIZE_A10, false),
            array('A10', Page::SIZE_A10, false),
            array('a10-landscape', Page::SIZE_A10, true),
            array('a10_landscape', Page::SIZE_A10, true),
            array('b0', Page::SIZE_B0, false),
            array('B0', Page::SIZE_B0, false),
            array('b0-landscape', Page::SIZE_B0, true),
            array('b0_landscape', Page::SIZE_B0, true),
            array('b1', Page::SIZE_B1, false),
            array('B1', Page::SIZE_B1, false),
            array('b1-landscape', Page::SIZE_B1, true),
            array('b1_landscape', Page::SIZE_B1, true),
            array('b2', Page::SIZE_B2, false),
            array('B2', Page::SIZE_B2, false),
            array('b2-landscape', Page::SIZE_B2, true),
            array('b2_landscape', Page::SIZE_B2, true),
            array('b3', Page::SIZE_B3, false),
            array('B3', Page::SIZE_B3, false),
            array('b3-landscape', Page::SIZE_B3, true),
            array('b3_landscape', Page::SIZE_B3, true),
            array('b4', Page::SIZE_B4, false),
            array('B4', Page::SIZE_B4, false),
            array('b4-landscape', Page::SIZE_B4, true),
            array('b4_landscape', Page::SIZE_B4, true),
            array('b5', Page::SIZE_B5, false),
            array('B5', Page::SIZE_B5, false),
            array('b5-landscape', Page::SIZE_B5, true),
            array('b5_landscape', Page::SIZE_B5, true),
            array('b6', Page::SIZE_B6, false),
            array('B6', Page::SIZE_B6, false),
            array('b6-landscape', Page::SIZE_B6, true),
            array('b6_landscape', Page::SIZE_B6, true),
            array('b7', Page::SIZE_B7, false),
            array('B7', Page::SIZE_B7, false),
            array('b7-landscape', Page::SIZE_B7, true),
            array('b7_landscape', Page::SIZE_B7, true),
            array('b8', Page::SIZE_B8, false),
            array('B8', Page::SIZE_B8, false),
            array('b8-landscape', Page::SIZE_B8, true),
            array('b8_landscape', Page::SIZE_B8, true),
            array('b9', Page::SIZE_B9, false),
            array('B9', Page::SIZE_B9, false),
            array('b9-landscape', Page::SIZE_B9, true),
            array('b9_landscape', Page::SIZE_B9, true),
            array('b10', Page::SIZE_B10, false),
            array('B10', Page::SIZE_B10, false),
            array('b10-landscape', Page::SIZE_B10, true),
            array('b10_landscape', Page::SIZE_B10, true),
            array('c0', Page::SIZE_C0, false),
            array('C0', Page::SIZE_C0, false),
            array('c0-landscape', Page::SIZE_C0, true),
            array('c0_landscape', Page::SIZE_C0, true),
            array('c1', Page::SIZE_C1, false),
            array('C1', Page::SIZE_C1, false),
            array('c1-landscape', Page::SIZE_C1, true),
            array('c1_landscape', Page::SIZE_C1, true),
            array('c2', Page::SIZE_C2, false),
            array('C2', Page::SIZE_C2, false),
            array('c2-landscape', Page::SIZE_C2, true),
            array('c2_landscape', Page::SIZE_C2, true),
            array('c3', Page::SIZE_C3, false),
            array('C3', Page::SIZE_C3, false),
            array('c3-landscape', Page::SIZE_C3, true),
            array('c3_landscape', Page::SIZE_C3, true),
            array('c4', Page::SIZE_C4, false),
            array('C4', Page::SIZE_C4, false),
            array('c4-landscape', Page::SIZE_C4, true),
            array('c4_landscape', Page::SIZE_C4, true),
            array('c5', Page::SIZE_C5, false),
            array('C5', Page::SIZE_C5, false),
            array('c5-landscape', Page::SIZE_C5, true),
            array('c5_landscape', Page::SIZE_C5, true),
            array('c6', Page::SIZE_C6, false),
            array('C6', Page::SIZE_C6, false),
            array('c6-landscape', Page::SIZE_C6, true),
            array('c6_landscape', Page::SIZE_C6, true),
            array('c7', Page::SIZE_C7, false),
            array('C7', Page::SIZE_C7, false),
            array('c7-landscape', Page::SIZE_C7, true),
            array('c7_landscape', Page::SIZE_C7, true),
            array('c8', Page::SIZE_C8, false),
            array('C8', Page::SIZE_C8, false),
            array('c8-landscape', Page::SIZE_C8, true),
            array('c8_landscape', Page::SIZE_C8, true),
            array('c9', Page::SIZE_C9, false),
            array('C9', Page::SIZE_C9, false),
            array('c9-landscape', Page::SIZE_C9, true),
            array('c9_landscape', Page::SIZE_C9, true),
            array('c10', Page::SIZE_C10, false),
            array('C10', Page::SIZE_C10, false),
            array('c10-landscape', Page::SIZE_C10, true),
            array('c10_landscape', Page::SIZE_C10, true),
            array('LETTER-landscape', Page::SIZE_LETTER, true),
            array('LETTER landscape', Page::SIZE_LETTER, true),
            array('letter', Page::SIZE_LETTER, false),
            array('legal', Page::SIZE_LEGAL, false),
            array('LEGAL landscape', Page::SIZE_LEGAL, true),
            array('LEGAL-landscape', Page::SIZE_LEGAL, true),
        );
    }
    
    /**
     * @test
     */
    public function watermarkShouldBeInTheMiddleOfPage()
    {
        $watermark = new Container();
        $watermark->setHeight(100);
        
        $this->page->setWatermark($watermark);
        
        $this->assertEquals(Node::VERTICAL_ALIGN_MIDDLE, $watermark->getAttribute('vertical-align'));
        $this->assertEquals($this->page->getHeight(), $watermark->getHeight());
        $this->assertEquals($this->page->getWidth(), $watermark->getWidth());
    }
    
    /**
     * @test
     * @dataProvider pageNumberProvider
     */
    public function loadTemplateDocumentWhileFormattingIfExists($numberOfPage)
    {
        $fileOfSourcePage = 'some/file.pdf';
        $width = 100;
        $height = 50;
        $numberOfSourceGcs = 3;
        
        $this->page->setAttribute('document-template', $fileOfSourcePage);
        
        if($numberOfPage !== null)
        {
            $pageContext = $this->getMockBuilder('PHPPdf\Core\Node\PageContext')
                                ->setMethods(array('getPageNumber'))
                                ->disableOriginalConstructor()
                                ->getMock();
                                
            $pageContext->expects($this->once())
                        ->method('getPageNumber')
                        ->will($this->returnValue($numberOfPage));
            $this->page->setContext($pageContext);
        }
        
        $document = $this->getMockBuilder('PHPPdf\Core\Document')
                         ->disableOriginalConstructor()
                         ->setMethods(array('loadEngine'))
                         ->getMock();
                         
        $engine = $this->getMockBuilder('PHPPdf\Core\Engine\Engine')
                       ->getMock();

        $copiedGc = $this->getMockBuilder('PHPPdf\Core\Engine\GraphicsContext')
                         ->getMock();

        $sourceGcs = array();
        for($i=0; $i<$numberOfSourceGcs; $i++)
        {
            $sourceGc = $this->getMockBuilder('PHPPdf\Core\Engine\GraphicsContext')
                             ->getMock();
            $sourceGcs[] = $sourceGc;
        }
                         
        $document->expects($this->once())
                 ->method('loadEngine')
                 ->with($fileOfSourcePage, 'utf-8')
                 ->will($this->returnValue($engine));
                 
        $engine->expects($this->once())
               ->method('getAttachedGraphicsContexts')
               ->will($this->returnValue($sourceGcs));
               
        $sourceGcIndex = $numberOfPage === null ? 0 : ($numberOfPage - 1) % $numberOfSourceGcs;

        $sourceGc = $sourceGcs[$sourceGcIndex];

        $sourceGc->expects($this->once())
                 ->method('copy')
                 ->will($this->returnValue($copiedGc));
        
        $copiedGc->expects($this->atLeastOnce())
                 ->method('getWidth')
                 ->will($this->returnValue($width));
        $copiedGc->expects($this->atLeastOnce())
                 ->method('getHeight')
                 ->will($this->returnValue($height));
           
        $this->page->format($document);
        
        $this->assertEquals($width, $this->page->getWidth());
        $this->assertEquals($height, $this->page->getHeight());
    }
    
    public function pageNumberProvider()
    {
        return array(
            array(null),
            array(1),
            array(6),
        );
    }
    
    /**
     * @test
     */
    public function setsPageSizeOnWidthOrHeightAttributeSet()
    {
        list($width, $height) = explode(':', $this->page->getAttribute('page-size'));
        
        $newWidth = 123;
        $this->page->setWidth($newWidth);
        
        $this->assertEquals($newWidth.':'.$height, $this->page->getAttribute('page-size'));
        
        $newHeight = 321;
        $this->page->setHeight($newHeight);
        
        $this->assertEquals($newWidth.':'.$newHeight, $this->page->getAttribute('page-size'));
    }
}