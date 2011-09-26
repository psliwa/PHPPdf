<?php

namespace PHPPdf\Test;

use PHPPdf\Util\DrawingTaskHeap;

use PHPPdf\Document,
    PHPPdf\Font\Registry as FontRegistry,
    PHPPdf\Node\Page;

class DocumentTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $document;

    public function setUp()
    {
        $this->document = new Document();
    }

    /**
     * @test
     */
    public function invokeDrawingTasksOfPagesWhenDrawMethodIsInvoked($assertArguments = true)
    {
        $tasks = array();
        for($i=0; $i<3; $i++)
        {
            $taskMock = $this->getMockBuilder('PHPPdf\Util\DrawingTask')
                             ->setMethods(array('__invoke'))
                             ->disableOriginalConstructor()
                             ->getMock();
            $taskMock->expects($this->once())
                     ->method('__invoke');
            $tasks[] = $taskMock;
        }
                 
        $mock = $this->getMock('\PHPPdf\Node\PageCollection', array('getAllDrawingTasks', 'format'));

        $matcher = $mock->expects($this->once())
                        ->method('format')
                        ->id(1);
        
        if($assertArguments)
        {
            $matcher->with($this->document);
        }

        $mock->expects($this->once())
             ->after(1)
             ->method('getAllDrawingTasks')
             ->will($this->returnValue($tasks));

        $this->document->draw($mock);
    }

    /**
     * @test
     */
    public function drawMethodCanBeMultiplyCalledIfDocumentStatusHaveResetByInitializeMethod()
    {
        $this->invokeDrawingTasksOfPagesWhenDrawMethodIsInvoked(false);
        $this->document->initialize();
        $this->invokeDrawingTasksOfPagesWhenDrawMethodIsInvoked(false);
    }

    /**
     * @test
     * @expectedException \LogicException
     */
    public function throwExceptionWhenDocumentIsDrawTwiceWithoutReset()
    {
        $mock = $this->getMock('\PHPPdf\Node\Page', array('collectOrderedDrawingTasks'));

        $mock->expects($this->once())
             ->method('collectOrderedDrawingTasks')
             ->will($this->returnValue(array()));

        $this->document->draw(array($mock));
        $this->document->draw(array($mock));
    }

    /**
     * @test
     * @expectedException \PHPPdf\Exception\DrawingException
     */
    public function drawingArgumentMustBeAnArrayOfPages()
    {
        $this->document->draw(array(new \PHPPdf\Node\Container()));
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
    public function createFormatterByClassName()
    {
        $className = 'PHPPdf\Formatter\FloatFormatter';

        $formatter = $this->document->getFormatter($className);

        $this->assertInstanceOf($className, $formatter);

        $this->assertTrue($formatter === $this->document->getFormatter($className));
    }

    /**
     * @test
     * @expectedException PHPPdf\Exception\Exception
     * @dataProvider invalidClassNamesProvider
     */
    public function throwExceptionIfPassInvalidFormatterClassName($className)
    {
        $this->document->getFormatter($className);
    }

    public function invalidClassNamesProvider()
    {
        return array(
            array('stdClass'),
            array('UnexistedClass'),
        );
    }
    
    /**
     * @test
     */
    public function useUnitConverterForConversions()
    {
        $unitConverter = $this->getMockBuilder('PHPPdf\Util\UnitConverter')
                              ->getMock();
                              
        $this->document->setUnitConverter($unitConverter);
        
        $actualUnit = '12px';
        $expectedUnit = 123;
        $actualPercent = '10%';
        $width = 120;
        $expectedPercent = 10;
        
        $unitConverter->expects($this->at(0))
                      ->method('convertUnit')
                      ->with($actualUnit)
                      ->will($this->returnValue($expectedUnit));
                      
        $unitConverter->expects($this->at(1))
                      ->method('convertPercentageValue')
                      ->with($actualPercent, $width)
                      ->will($this->returnValue($expectedPercent));
                      
        $this->assertEquals($expectedUnit, $this->document->convertUnit($actualUnit));
        $this->assertEquals($expectedPercent, $this->document->convertPercentageValue($actualPercent, $width));
    }
}