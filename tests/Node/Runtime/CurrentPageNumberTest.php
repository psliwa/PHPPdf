<?php

use PHPPdf\Util\DrawingTask;
use PHPPdf\Node\Runtime\CurrentPageNumber,
    PHPPdf\Document,
    PHPPdf\Node\DynamicPage,
    PHPPdf\Node\Page;

class CurrentPageNumberTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PHPPdf\Node\Node
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

        $tasks = $this->node->getDrawingTasks(new Document());
        $this->assertEmpty($tasks);
    }

    private function getPageMock()
    {
        $mock = $this->getMock('PHPPdf\Node\Page', array('markAsRuntimeNode'));
        $mock->expects($this->once())
             ->method('markAsRuntimeNode');

        return $mock;
    }

    /**
     * @test
     */
    public function cannotMergeEnhancements()
    {
        $this->node->mergeEnhancementAttributes('name', array('name' => 'value'));

        $this->assertEmpty($this->node->getEnhancementsAttributes());
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
     */
    public function drawingAfterEvaluating()
    {
        $pageMock = $this->getMock('PHPPdf\Node\Page', array('getContext'));
        $contextMock = $this->getMock('PHPPdf\Node\PageContext', array('getPageNumber'), array(5, new DynamicPage()));

        $pageMock->expects($this->atLeastOnce())
                 ->method('getContext')
                 ->will($this->returnValue($contextMock));

        $pageNumber = 5;
        $contextMock->expects($this->atLeastOnce())
                    ->method('getPageNumber')
                    ->will($this->returnValue(5));

        $this->node->setParent($pageMock);
        $linePart = $this->getMockBuilder('PHPPdf\Node\Paragraph\LinePart')
                         ->setMethods(array('setWords', 'getDrawingTasks'))
                         ->disableOriginalConstructor()
                         ->getMock();
                         
        $linePart->expects($this->at(0))
                 ->method('setWords')
                 ->with($pageNumber);
                         
        $drawingTaskStub = new DrawingTask(function(){});
        $linePart->expects($this->at(1))
                 ->method('getDrawingTasks')
                 ->will($this->returnValue(array($drawingTaskStub)));
                 
        $this->node->addLinePart($linePart);

        $this->node->evaluate();
        $tasks = $this->node->getDrawingTasks(new Document());
        $this->assertEquals(array($drawingTaskStub), $tasks);
        $this->assertEquals($pageNumber, $this->node->getText());
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