<?php

namespace PHPPdf\Test;

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
                 
        $taskMock2 = $this->getMockBuilder('PHPPdf\Util\DrawingTask')
                          ->setMethods(array('__invoke'))
                          ->disableOriginalConstructor()
                          ->getMock();

        $mock = $this->getMock('\PHPPdf\Node\PageCollection', array('getOrderedDrawingTasks', 'getUnorderedDrawingTasks', 'getPostDrawingTasks', 'format'));

        $matcher = $mock->expects($this->once())
                        ->method('format')
                        ->id(1);
        
        if($assertArguments)
        {
            $matcher->with($this->document);
        }

        $mock->expects($this->once())
             ->after(1)
             ->method('getOrderedDrawingTasks')
             ->will($this->returnValue(array($tasks[0])));
        $mock->expects($this->once())
             ->after(1)
             ->method('getUnorderedDrawingTasks')
             ->will($this->returnValue(array($tasks[1])));
        $mock->expects($this->once())
             ->after(1)
             ->method('getPostDrawingTasks')
             ->will($this->returnValue(array($tasks[2])));

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
        $mock = $this->getMock('\PHPPdf\Node\Page', array('getOrderedDrawingTasks'));

        $mock->expects($this->once())
             ->method('getOrderedDrawingTasks')
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
}