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
                             ->setMethods(array('invoke'))
                             ->disableOriginalConstructor()
                             ->getMock();
            $taskMock->expects($this->once())
                     ->method('invoke');
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
        $enhancementsParameters = array('border' => array('color' => 'red', 'name' => 'border'), 'background' => array('name' => 'background', 'color' => 'pink', 'repeat' => 'none'), 'empty' => array('name' => 'empty'));
        $enhancementStub = $this->getMockBuilder('PHPPdf\Enhancement\Border')
                                ->setMethods(array('isEmpty'))
                                ->getMock();
                                
        $enhancementStub->expects($this->exactly(2))
                        ->method('isEmpty')
                        ->will($this->returnValue(false));
                                
        $emptyEnhancementStub = $this->getMockBuilder('PHPPdf\Enhancement\Border')
                                     ->setMethods(array('isEmpty'))
                                     ->getMock();
                                     
        $emptyEnhancementStub->expects($this->once())
                             ->method('isEmpty')
                             ->will($this->returnValue(true));

        $enhancementBagMock = $this->getMock('PHPPdf\Util\AttributeBag', array('getAll'));
        $enhancementBagMock->expects($this->once())
                           ->method('getAll')
                           ->will($this->returnValue($enhancementsParameters));
                           
        $enhancementsMap = array(
            'border' => $enhancementStub,
            'background' => $enhancementStub,
            'empty' => $emptyEnhancementStub,
        );

        $enhancementFactoryMock = $this->getMock('PHPPdf\Enhancement\Factory', array('create'));
        
        $at = 0;
        foreach($enhancementsParameters as $name => $params)
        {
            $enhancementFactoryMock->expects($this->at($at++))
                                   ->method('create')
                                   ->with($this->equalTo($name), $this->equalTo(array_diff_key($params, array('name' => true))))
                                   ->will($this->returnValue($enhancementsMap[$name]));
            
        }

        $this->document->setEnhancementFactory($enhancementFactoryMock);

        $enhancements = $this->document->getEnhancements($enhancementBagMock);

        $this->assertTrue(count($enhancements) === 2, 'empty enhancement should not be returned by Document');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function failureOfEnhancementCreation()
    {
        $enhancements = array('some' => array('color' => 'red'));

        $enhancementBagMock = $this->getMock('PHPPdf\Util\AttributeBag', array('getAll'));
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