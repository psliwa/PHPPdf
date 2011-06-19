<?php

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
     */
    public function renderListTypeForEachChildren()
    {
        $page = $this->getMock('PHPPdf\Glyph\Page', array('getGraphicsContext'));
        
        $gc = $this->getMock('PHPPdf\Glyph\GraphicsContext', array('drawText'), array(), '', false);
        
        $page->expects($this->atLeastOnce())
             ->method('getGraphicsContext')
             ->will($this->returnValue($gc));
             
        $this->list->setParent($page);
        
        $children = array(
            $this->objectMother->getGlyphMock(20, 100, 100, 20, array('draw')),
            $this->objectMother->getGlyphMock(20, 80, 100, 20, array('draw')),
        );
        
        $listType = BasicList::TYPE_CIRCLE;
        $listPosition = BasicList::POSITION_OUTSIDE;
        
        $gc->expects($this->exactly(\count($children)))
           ->method('drawText');
        
        $this->list->setAttribute('type', $listType)
                   ->setAttribute('position', $listPosition);
        
        foreach($children as $child)
        {
            $this->list->add($child);
        }
        
        $tasks = $this->list->getDrawingTasks(new Document());
        
        foreach($tasks as $task)
        {
            $task->invoke();
        }
    }
}