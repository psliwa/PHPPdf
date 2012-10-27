<?php

namespace PHPPdf\Test\Core\Node\Runtime;

use PHPPdf\Core\DrawingTaskHeap;

use PHPPdf\Core\DrawingTask;
use PHPPdf\Core\Node\Runtime\CurrentPageNumber,
    PHPPdf\Core\Document,
    PHPPdf\Core\Node\DynamicPage,
    PHPPdf\Core\Node\Page;

class CurrentPageNumberTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    /**
     * @var PHPPdf\Core\Node\Node
     */
    private $node;

    public function setUp()
    {
        $this->node = new CurrentPageNumber();
    }

    /**
     * @test
     */
    public function drawing()
    {
        $mock = $this->getPageMock();

        $this->node->setParent($mock);

        $tasks = new DrawingTaskHeap();
        $this->node->collectOrderedDrawingTasks($this->createDocumentStub(), $tasks);
        $this->assertEquals(0, count($tasks));
    }

    private function getPageMock()
    {
        $mock = $this->getMock('PHPPdf\Core\Node\Page', array('markAsRuntimeNode'));
        $mock->expects($this->once())
             ->method('markAsRuntimeNode');

        return $mock;
    }

    /**
     * @test
     */
    public function cannotMergeComplexAttributes()
    {
        $this->node->mergeComplexAttributes('name', array('name' => 'value'));

        $this->assertEmpty($this->node->getComplexAttributes());
    }

    /**
     * @test
     */
    public function valueBeforeEvaluation()
    {
        $dummyText = $this->node->getAttribute('dummy-text');
        $text = $this->node->getText();

        $this->assertNotEmpty($dummyText);
        $this->assertEquals($dummyText, $text);
    }
    
    /**
     * @test
     * @dataProvider offsetProvider
     */
    public function drawingAfterEvaluating($offset)
    {
        $pageMock = $this->getMock('PHPPdf\Core\Node\Page', array('getContext'));
        $contextMock = $this->getMock('PHPPdf\Core\Node\PageContext', array('getPageNumber'), array(5, new DynamicPage()));

        $pageMock->expects($this->atLeastOnce())
                 ->method('getContext')
                 ->will($this->returnValue($contextMock));

        $pageNumber = 5;
        $contextMock->expects($this->atLeastOnce())
                    ->method('getPageNumber')
                    ->will($this->returnValue($pageNumber));
                    
        $format = 'abc%s.';
        $this->node->setAttribute('format', $format);
        $this->node->setAttribute('offset', $offset);

        $this->node->setParent($pageMock);
        $linePart = $this->getMockBuilder('PHPPdf\Core\Node\Paragraph\LinePart')
                         ->setMethods(array('setWords', 'collectOrderedDrawingTasks'))
                         ->disableOriginalConstructor()
                         ->getMock();
                         
        $expectedText = sprintf($format, $pageNumber + $offset);
        $linePart->expects($this->at(0))
                 ->method('setWords')
                 ->with($expectedText);

                 
        $document = $this->createDocumentStub();
        $drawingTaskStub = new DrawingTask(function(){});
        $tasks = new DrawingTaskHeap();

        $linePart->expects($this->at(1))
                 ->method('collectOrderedDrawingTasks')
                 ->with($this->isInstanceOf('PHPPdf\Core\Document'), $this->isInstanceOf('PHPPdf\Core\DrawingTaskHeap'))
                 ->will($this->returnCallback(function() use($tasks, $drawingTaskStub){
                     $tasks->insert($drawingTaskStub);
                 }));
                 
        $this->node->addLinePart($linePart);

        $this->node->evaluate();

        $this->node->collectOrderedDrawingTasks($this->createDocumentStub(), $tasks);
        $this->assertEquals(1, count($tasks));
        $this->assertEquals($expectedText, $this->node->getText());
    }
    
    public function offsetProvider()
    {
        return array(
            array(0),
            array(5),
        );
    }

    /**
     * @test
     */
    public function settingPage()
    {
        $page = new Page();

        $this->node->setPage($page);

        $this->assertTrue($page === $this->node->getPage());
    }

    /**
     * @test
     */
    public function afterCopyParentIsntDetached()
    {
        $page = new Page();

        $this->node->setParent($page);
        $copy = $this->node->copyAsRuntime();

        $this->assertTrue($copy->getParent() === $page);
    }
}