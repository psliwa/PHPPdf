<?php

use PHPPdf\Document;
use PHPPdf\Glyph\Page;

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
    public function gettingUnexistsAttribute()
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
    public function drawing()
    {       
        $mock = $this->getMock('\PHPPdf\Glyph\Page', array('doDraw'));
        $mock->expects($this->once())
             ->method('doDraw');

        $this->document->draw(array($mock));
    }

    /**
     * @test
     */
    public function drawingAndStatusReset()
    {
        $this->drawing();
        $this->document->initialize();
        $this->drawing();
    }

    /**
     * @test
     * @expectedException \LogicException
     */
    public function drawingFailure()
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

        $formatter = $this->getMock('PHPPdf\Formatter\Formatter', array('preFormat', 'postFormat', 'getDocument'), array($this->document));

        $this->document->addFormatter($formatter);
        $formatters = $this->document->getFormatters();
        
        $this->assertEquals(1, count($formatters));
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
     */
    public function gettingFontRegistry()
    {
        $fontRegistry = $this->document->getFontRegistry();

        $this->assertInstanceOf('PHPPdf\Font\Registry', $fontRegistry);
    }
}