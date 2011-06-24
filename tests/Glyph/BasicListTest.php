<?php

use PHPPdf\Glyph\Glyph;
use PHPPdf\Glyph\Container;
use PHPPdf\Document;
use PHPPdf\Glyph\BasicList;

class BasicListTest extends TestCase
{
    private $list;
    private $objectMother;
    
    public function init()
    {
        $this->objectMother = new GenericGlyphObjectMother($this);
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
        $page = $this->getMock('PHPPdf\Glyph\Page', array('getGraphicsContext', 'getAttribute'));
        
        $gc = $this->getMock('PHPPdf\Glyph\GraphicsContext', array(), array(), '', false, false);
        
        $page->expects($this->atLeastOnce())
             ->method('getGraphicsContext')
             ->will($this->returnValue($gc));
             
        $this->list->setParent($page);
        $enumerationStrategy = $this->getMock('PHPPdf\Glyph\BasicList\EnumerationStrategy', array('drawEnumeration', 'reset', 'getWidthOfTheBiggestPosibleEnumerationElement', 'setIndex', 'setVisualIndex'));
        $enumerationStrategy->expects($this->once())
                            ->method('setIndex')
                            ->with(0);
        
        $this->list->setEnumerationStrategy($enumerationStrategy);

        for($i=0; $i<$numberOfChildren; $i++)
        {
            $this->list->add(new Container());
            $enumerationStrategy->expects($this->at($i+1))
                                ->method('drawEnumeration')
                                ->with($this->list, $gc, $i);
        }
        $enumerationStrategy->expects($this->at($i))
                            ->method('reset');
        
        $tasks = $this->list->getDrawingTasks(new Document());
        
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
        
        $font = $this->getMock('PHPPdf\Font\Font', array(), array(), '', false);
        $this->list->setAttribute('font-type', $font);
        $enumerationStrategy = $this->list->getEnumerationStrategy();
        
        $this->assertInstanceOf($expectedEnumerationStrategyClass, $enumerationStrategy);
    }
    
    public function enumerationProvider()
    {
        return array(
            array(BasicList::TYPE_CIRCLE, 'PHPPdf\Glyph\BasicList\UnorderedEnumerationStrategy'),
            array(BasicList::TYPE_SQUARE, 'PHPPdf\Glyph\BasicList\UnorderedEnumerationStrategy'),
            array(BasicList::TYPE_DISC, 'PHPPdf\Glyph\BasicList\UnorderedEnumerationStrategy'),
            array(BasicList::TYPE_NONE, 'PHPPdf\Glyph\BasicList\UnorderedEnumerationStrategy'),
            array(BasicList::TYPE_NUMERIC, 'PHPPdf\Glyph\BasicList\OrderedEnumerationStrategy'),
        );
    }
    
    /**
     * @test
     */
    public function createNewEnumerationStrategyOnlyWhenTypeWasChanged()
    {
        $font = $this->getMock('PHPPdf\Font\Font', array(), array(), '', false);
        $this->list->setAttribute('font-type', $font);
        
        $this->list->setAttribute('type', BasicList::TYPE_CIRCLE);
        
        $this->assertTrue($this->list->getEnumerationStrategy() === $this->list->getEnumerationStrategy());
        
        $enumerationStrategy = $this->list->getEnumerationStrategy();
        
        $this->list->setAttribute('type', BasicList::TYPE_NUMERIC);
        
        $this->assertFalse($enumerationStrategy === $this->list->getEnumerationStrategy());
    }
}