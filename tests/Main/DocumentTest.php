<?php

use PHPPdf\Document,
    PHPPdf\Font\Registry as FontRegistry,
    PHPPdf\Glyph\Page;

class DocumentTest extends PHPUnit_Framework_TestCase
{
    private $document;

    public function setUp()
    {
        $this->document = new Document();
    }

    /**
     * @test
     */
    public function settingAttribte()
    {
        $this->document->setAttribute(Document::ATTR_PAGE_SIZE, Document::SIZE_A4);
        $this->assertEquals(Document::SIZE_A4, $this->document->getAttribute(Document::ATTR_PAGE_SIZE));
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function throwExceptionIfCalledAttributeDosntExist()
    {
        $this->document->getAttribute(1235566);
    }

    /**
     * @test
     */
    public function attributesDefaultValues()
    {
        $this->assertEquals(Document::SIZE_A4, $this->document->getAttribute(Document::ATTR_PAGE_SIZE));
    }

    /**
     * @test
     */
    public function invokeDrawingTasksOfPagesWhenDrawMethodIsInvoked()
    {
        $taskMock = $this->getMock('PHPPdf\Util\DrawingTask', array('__invoke'), array(), '', false);
        $taskMock->expects($this->once())
                 ->method('__invoke');

        $mock = $this->getMock('\PHPPdf\Glyph\Page', array('getDrawingTasks'));
        $mock->expects($this->once())
             ->method('getDrawingTasks')
             ->will($this->returnValue(array($taskMock)));

        $this->document->draw(array($mock));
    }

    /**
     * @test
     */
    public function drawMethodCanBeMultiplyCalledIfDocumentStatusHaveResetByInitializeMethod()
    {
        $this->invokeDrawingTasksOfPagesWhenDrawMethodIsInvoked();
        $this->document->initialize();
        $this->invokeDrawingTasksOfPagesWhenDrawMethodIsInvoked();
    }

    /**
     * @test
     * @expectedException \LogicException
     */
    public function throwExceptionWhenDocumentIsDrawTwiceWithoutReset()
    {
        $drawingMethods = array('preDraw', 'doDraw', 'postDraw');
        $mock = $this->getMock('\PHPPdf\Glyph\Page', array_merge($drawingMethods));

        foreach($drawingMethods as $method)
        {
            $mock->expects($this->once())
                 ->method($method)
                 ->will($this->returnValue(true));
        }

        $this->document->draw(array($mock));
        $this->document->draw(array($mock));
    }

    /**
     * @test
     */
    public function gettingAndSettingFormatters()
    {
        $formatters = $this->document->getFormatters();

        $this->assertTrue(count($formatters) === 0);

        $formatter = $this->getMock('PHPPdf\Formatter\BaseFormatter');

        $this->document->addFormatter($formatter);
        $formatters = $this->document->getFormatters();
        
        $this->assertEquals(1, count($formatters));
    }

    /**
     * @test
     */
    public function setDocumentForAddedFormatter()
    {
        $formatter = $this->getMock('PHPPdf\Formatter\BaseFormatter', array('preFormat', 'postFormat', 'getDocument', 'setDocument'));
        $formatter->expects($this->once())
                  ->method('setDocument')
                  ->with($this->document);

        $this->document->addFormatter($formatter);
    }

    /**
     * @test
     * @expectedException \PHPPdf\Exception\DrawingException
     */
    public function drawingArgumentMustBeAnArrayOfPages()
    {
        $this->document->draw(array(new PHPPdf\Glyph\Container()));
    }

    /**
     * @test
     */
    public function creationOfEnhancements()
    {
        $enhancementsParameters = array('border' => array('color' => 'red', 'name' => 'border'), 'background' => array('name' => 'background', 'color' => 'pink', 'repeat' => 'none'));
        $enhancementStub = $this->getMock('PHPPdf\Enhancement\Border');

        $enhancementBagMock = $this->getMock('PHPPdf\Enhancement\EnhancementBag', array('getAll'));
        $enhancementBagMock->expects($this->once())
                           ->method('getAll')
                           ->will($this->returnValue($enhancementsParameters));

        $enhancementFactoryMock = $this->getMock('PHPPdf\Enhancement\Factory', array('create'));
        $enhancementFactoryMock->expects($this->at(0))
                               ->method('create')
                               ->with($this->equalTo('border'), $this->equalTo(array_diff_key($enhancementsParameters['border'], array('name' => true))))
                               ->will($this->returnValue($enhancementStub));

        $enhancementFactoryMock->expects($this->at(1))
                               ->method('create')
                               ->with($this->equalTo('background'), $this->equalTo(array_diff_key($enhancementsParameters['background'], array('name' => true))))
                               ->will($this->returnValue($enhancementStub));

        $this->document->setEnhancementFactory($enhancementFactoryMock);

        $enhancements = $this->document->getEnhancements($enhancementBagMock);

        $this->assertTrue(count($enhancements) === 2);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function failureOfEnhancementCreation()
    {
        $enhancements = array('some' => array('color' => 'red'));

        $enhancementBagMock = $this->getMock('PHPPdf\Enhancement\EnhancementBag', array('getAll'));
        $enhancementBagMock->expects($this->once())
                           ->method('getAll')
                           ->will($this->returnValue($enhancements));

        $enhancementFactoryMock = $this->getMock('PHPPdf\Enhancement\Factory', array('create'));
        $enhancementFactoryMock->expects($this->never())
                               ->method('create');

        $this->document->setEnhancementFactory($enhancementFactoryMock);

        $this->document->getEnhancements($enhancementBagMock);
    }

    /**
     * @test
     * @expectedException PHPPdf\Exception\Exception
     */
    public function throwExceptionIfFontRegistryIsntSet()
    {
        $this->document->getFontRegistry();
    }

    /**
     * @test
     */
    public function getFontRegistryIfPreviouslyHasBeenSet()
    {
        $registry = new FontRegistry();
        $this->document->setFontRegistry($registry);

        $this->assertTrue($this->document->getFontRegistry() === $registry);
    }
}