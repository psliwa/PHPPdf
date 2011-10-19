<?php

namespace PHPPdf\Test\Core;

use PHPPdf\Core\DrawingTaskHeap;
use PHPPdf\Core\Document,
    PHPPdf\Font\Registry as FontRegistry,
    PHPPdf\Core\Node\Page;

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
            $taskMock = $this->getMockBuilder('PHPPdf\Core\DrawingTask')
                             ->setMethods(array('invoke'))
                             ->disableOriginalConstructor()
                             ->getMock();
            $taskMock->expects($this->once())
                     ->method('invoke');
            $tasks[] = $taskMock;
        }
                 
        $mock = $this->getMock('\PHPPdf\Core\Node\PageCollection', array('getAllDrawingTasks', 'format'));

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
        $mock = $this->getMock('\PHPPdf\Core\Node\Page', array('collectOrderedDrawingTasks'));

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
        $this->document->draw(array(new \PHPPdf\Core\Node\Container()));
    }

    /**
     * @test
     */
    public function creationOfComplexAttributes()
    {
        $complexAttributesParameters = array('border' => array('color' => 'red', 'name' => 'border'), 'background' => array('name' => 'background', 'color' => 'pink', 'repeat' => 'none'), 'empty' => array('name' => 'empty'));
        $complexAttributeStub = $this->getMockBuilder('PHPPdf\Core\ComplexAttribute\Border')
                                ->setMethods(array('isEmpty'))
                                ->getMock();
                                
        $complexAttributeStub->expects($this->exactly(2))
                        ->method('isEmpty')
                        ->will($this->returnValue(false));
                                
        $emptyComplexAttributeStub = $this->getMockBuilder('PHPPdf\Core\ComplexAttribute\Border')
                                     ->setMethods(array('isEmpty'))
                                     ->getMock();
                                     
        $emptyComplexAttributeStub->expects($this->once())
                             ->method('isEmpty')
                             ->will($this->returnValue(true));

        $complexAttributeBagMock = $this->getMock('PHPPdf\Core\AttributeBag', array('getAll'));
        $complexAttributeBagMock->expects($this->once())
                           ->method('getAll')
                           ->will($this->returnValue($complexAttributesParameters));
                           
        $complexAttributesMap = array(
            'border' => $complexAttributeStub,
            'background' => $complexAttributeStub,
            'empty' => $emptyComplexAttributeStub,
        );

        $complexAttributeFactoryMock = $this->getMock('PHPPdf\Core\ComplexAttribute\Factory', array('create'));
        
        $at = 0;
        foreach($complexAttributesParameters as $name => $params)
        {
            $complexAttributeFactoryMock->expects($this->at($at++))
                                   ->method('create')
                                   ->with($this->equalTo($name), $this->equalTo(array_diff_key($params, array('name' => true))))
                                   ->will($this->returnValue($complexAttributesMap[$name]));
            
        }

        $this->document->setComplexAttributeFactory($complexAttributeFactoryMock);

        $complexAttributes = $this->document->getComplexAttributes($complexAttributeBagMock);

        $this->assertTrue(count($complexAttributes) === 2, 'empty complexAttribute should not be returned by Document');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function failureOfComplexAttributeCreation()
    {
        $complexAttributes = array('some' => array('color' => 'red'));

        $complexAttributeBagMock = $this->getMock('PHPPdf\Core\AttributeBag', array('getAll'));
        $complexAttributeBagMock->expects($this->once())
                           ->method('getAll')
                           ->will($this->returnValue($complexAttributes));

        $complexAttributeFactoryMock = $this->getMock('PHPPdf\Core\ComplexAttribute\Factory', array('create'));
        $complexAttributeFactoryMock->expects($this->never())
                               ->method('create');

        $this->document->setComplexAttributeFactory($complexAttributeFactoryMock);

        $this->document->getComplexAttributes($complexAttributeBagMock);
    }

    /**
     * @test
     */
    public function createFormatterByClassName()
    {
        $className = 'PHPPdf\Core\Formatter\FloatFormatter';

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
        $unitConverter = $this->getMockBuilder('PHPPdf\Core\UnitConverter')
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