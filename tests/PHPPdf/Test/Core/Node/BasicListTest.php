<?php

namespace PHPPdf\Test\Core\Node;

use PHPPdf\Core\DrawingTaskHeap;

use PHPPdf\Core\Node\Node;
use PHPPdf\Core\Node\Container;
use PHPPdf\Core\Document;
use PHPPdf\Core\Node\BasicList;
use PHPPdf\ObjectMother\NodeObjectMother;

class BasicListTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $list;
    private $objectMother;
    
    public function init()
    {
        $this->objectMother = new NodeObjectMother($this);
    }
    
    public function setUp()
    {
        $this->list = new BasicList();
    }
    
    /**
     * @test
     * @dataProvider sizesProvider
     */
    public function renderListTypeForEachChildren($numberOfChildren)
    {
        $page = $this->getMock('PHPPdf\Core\Node\Page', array('getGraphicsContext', 'getAttribute'));
        
        $gc = $this->getMockBuilder('PHPPdf\Core\Engine\GraphicsContext')
                   ->getMock();
        
        $page->expects($this->atLeastOnce())
             ->method('getGraphicsContext')
             ->will($this->returnValue($gc));
             
        $this->list->setParent($page);
        $enumerationStrategy = $this->getMockBuilder('PHPPdf\Core\Node\BasicList\EnumerationStrategy')
                                    ->getMock();
        $enumerationStrategy->expects($this->once())
                            ->method('setIndex')
                            ->with(0);
        $document = $this->createDocumentStub();
        
        $this->list->setEnumerationStrategy($enumerationStrategy);

        for($i=0; $i<$numberOfChildren; $i++)
        {
            $this->list->add(new Container());
            $enumerationStrategy->expects($this->at($i+1))
                                ->method('drawEnumeration')
                                ->with($document, $this->list, $gc, $i);
        }
        $enumerationStrategy->expects($this->at($i))
                            ->method('reset');
        
        $tasks = new DrawingTaskHeap();
        $this->list->collectOrderedDrawingTasks($document, $tasks);
        
        foreach($tasks as $task)
        {
            $task->invoke();
        }
    }
    
    public function sizesProvider()
    {
        return array(
            array(5),
            array(10),
        );
    }
    
    /**
     * @test
     */
    public function acceptHumanReadableTypeAttributeValue()
    {
        $types = array(
            'circle' => BasicList::TYPE_CIRCLE,
            'disc' => BasicList::TYPE_DISC,
            'square' => BasicList::TYPE_SQUARE,
            'none' => BasicList::TYPE_NONE,
        );
        
        foreach($types as $name => $value)
        {
            $this->list->setAttribute('type', $name);
            
            $this->assertEquals($value, $this->list->getAttribute('type'));
        }
    }
    
    /**
     * @test
     * @dataProvider enumerationProvider
     */
    public function determineEnumerationStrategyOnType($type, $expectedEnumerationStrategyClass)
    {
        $this->list->setAttribute('type', $type);
        
        $factory = $this->getMock('PHPPdf\Core\Node\BasicList\EnumerationStrategyFactory', array('create'));
        
        $expectedStrategy = new $expectedEnumerationStrategyClass();
        $factory->expects($this->once())
                ->method('create')
                ->with($type)
                ->will($this->returnValue($expectedStrategy));
                
        $this->list->setEnumerationStrategyFactory($factory);
        
        $this->list->assignEnumerationStrategyFromFactory();
        
        $enumerationStrategy = $this->list->getEnumerationStrategy();
        
        $this->assertTrue($expectedStrategy === $enumerationStrategy);
    }
    
    public function enumerationProvider()
    {
        return array(
            array(BasicList::TYPE_CIRCLE, 'PHPPdf\Core\Node\BasicList\UnorderedEnumerationStrategy'),
            array(BasicList::TYPE_SQUARE, 'PHPPdf\Core\Node\BasicList\UnorderedEnumerationStrategy'),
            array(BasicList::TYPE_DISC, 'PHPPdf\Core\Node\BasicList\UnorderedEnumerationStrategy'),
            array(BasicList::TYPE_NONE, 'PHPPdf\Core\Node\BasicList\UnorderedEnumerationStrategy'),
            array(BasicList::TYPE_DECIMAL, 'PHPPdf\Core\Node\BasicList\OrderedEnumerationStrategy'),
        );
    }
    
    /**
     * @test
     */
    public function createNewEnumerationStrategyOnlyWhenTypeWasChanged()
    {
        $font = $this->getMockBuilder('PHPPdf\Core\Engine\Font')
                     ->getMock();
        $this->list->setAttribute('font-type', $font);
        
        $type = BasicList::TYPE_CIRCLE;
        $this->list->setAttribute('type', $type);
        
        $factory = $this->getMock('PHPPdf\Core\Node\BasicList\EnumerationStrategyFactory', array('create'));
        
        $strategyStub = 'some-stub1';
        $factory->expects($this->once())
                ->method('create')
                ->with($type)
                ->will($this->returnValue($strategyStub));
        $this->list->setEnumerationStrategyFactory($factory);
        
        $this->assertTrue($strategyStub === $this->list->getEnumerationStrategy());
        $this->assertTrue($strategyStub === $this->list->getEnumerationStrategy());
        
        $enumerationStrategy = $this->list->getEnumerationStrategy();
        
        $type = BasicList::TYPE_DECIMAL;
        $strategyStub = 'some-stub2';
        
        $factory = $this->getMock('PHPPdf\Core\Node\BasicList\EnumerationStrategyFactory', array('create'));
        $factory->expects($this->once())
                ->method('create')
                ->with($type)
                ->will($this->returnValue($strategyStub));
        $this->list->setEnumerationStrategyFactory($factory);
        
        $this->list->setAttribute('type', $type);
        
        $this->assertFalse($enumerationStrategy === $this->list->getEnumerationStrategy());
    }
}