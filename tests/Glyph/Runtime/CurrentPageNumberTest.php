<?php

use PHPPdf\Glyph\Runtime\CurrentPageNumber,
    PHPPdf\Document,
    PHPPdf\Glyph\DynamicPage,
    PHPPdf\Glyph\Page;

class CurrentPageNumberTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PHPPdf\Glyph\Glyph
     */
    private $glyph;

    public function setUp()
    {
        $this->glyph = new CurrentPageNumber();
    }

    /**
     * @test
     */
    public function drawing()
    {
        $mock = $this->getPageMock();

        $this->glyph->setParent($mock);

        $tasks = $this->glyph->getDrawingTasks(new Document());
        $this->assertEmpty($tasks);
    }

    private function getPageMock()
    {
        $mock = $this->getMock('PHPPdf\Glyph\Page', array('markAsRuntimeGlyph'));
        $mock->expects($this->once())
             ->method('markAsRuntimeGlyph');

        return $mock;
    }

    /**
     * @test
     */
    public function cannotMergeEnhancements()
    {
        $this->glyph->mergeEnhancementAttributes('name', array('name' => 'value'));

        $this->assertEmpty($this->glyph->getEnhancementsAttributes());
    }

    /**
     * @test
     */
    public function valueBeforeEvaluation()
    {
        $dummyText = $this->glyph->getAttribute('dummy-text');
        $text = $this->glyph->getText();

        $this->assertNotEmpty($dummyText);
        $this->assertEquals($dummyText, $text);
    }
    
    /**
     * @test
     */
    public function drawingAfterEvaluating()
    {
        $pageMock = $this->getMock('PHPPdf\Glyph\Page', array('getContext'));
        $contextMock = $this->getMock('PHPPdf\Glyph\PageContext', array('getPageNumber'), array(5, new DynamicPage()));

        $pageMock->expects($this->atLeastOnce())
                 ->method('getContext')
                 ->will($this->returnValue($contextMock));

        $pageNumber = 5;
        $contextMock->expects($this->atLeastOnce())
                    ->method('getPageNumber')
                    ->will($this->returnValue(5));

        $this->glyph->setParent($pageMock);

        $this->glyph->evaluate();
        $tasks = $this->glyph->getDrawingTasks(new Document());
        $this->assertEquals(1, count($tasks));
        $this->assertEquals($pageNumber, $this->glyph->getText());
        $this->assertEquals(array(array($pageNumber)), $this->glyph->getWordsInRows());
    }

    /**
     * @test
     */
    public function settingPage()
    {
        $page = new Page();

        $this->glyph->setPage($page);

        $this->assertTrue($page === $this->glyph->getPage());
    }

    /**
     * @test
     */
    public function afterCopyParentIsntDetached()
    {
        $page = new Page();

        $this->glyph->setParent($page);
        $copy = $this->glyph->copy();

        $this->assertTrue($copy->getParent() === $page);
    }
}