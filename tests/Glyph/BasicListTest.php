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
     * @dataProvider sizesProvider
     */
    public function renderListTypeForEachChildren(array $childrenSizes, $listPosition, $fontSize, $widthStub, $enumMargin)
    {
        $page = $this->getMock('PHPPdf\Glyph\Page', array('getGraphicsContext', 'getAttribute'));
        
        $gc = $this->getMock('PHPPdf\Glyph\GraphicsContext', array('drawText'), array(), '', false);
        
        $page->expects($this->atLeastOnce())
             ->method('getGraphicsContext')
             ->will($this->returnValue($gc));
             
        $encodingStub = 'utf-8';
        $page->expects($this->once())
             ->method('getAttribute')
             ->with('encoding')
             ->will($this->returnValue($encodingStub));
             
        $listType = BasicList::TYPE_CIRCLE;
        $font = $this->getMock('PHPPdf\Font\Font', array('getCharsWidth'), array(), '', false);
        $font->expects($this->once())
             ->method('getCharsWidth')
             ->with(array(ord($listType)), $fontSize)
             ->will($this->returnValue($widthStub));
             
        $this->list->setParent($page);
        $this->list->setAttribute('font-type', $font)
                   ->setAttribute('font-size', $fontSize)
                   ->setAttribute('type', $listType)
                   ->setAttribute('position', $listPosition);

        $children = array();
        foreach($childrenSizes as $at => $sizes)
        {
            $marginLeft = 10;
            $child = $this->objectMother->getGlyphMock($sizes[0], $sizes[1], $sizes[2], $sizes[3], array('draw', 'getMarginLeft'));
            $child->expects($this->atLeastOnce())
                  ->method('getMarginLeft')
                  ->will($this->returnValue($marginLeft));
            $children[] = $child;
            
            $expectedXCoord = $sizes[0] + ($listPosition == BasicList::POSITION_OUTSIDE ? -$widthStub : 0) - $marginLeft;
            $gc->expects($this->at($at))
               ->method('drawText')
               ->with($listType, $expectedXCoord, $sizes[1] - $fontSize, $encodingStub);
        }
        
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
    
    public function sizesProvider()
    {
        return array(
            array(
                array(
                    array(20, 100, 100, 20),
                    array(20, 80, 100, 20),
                ),
                BasicList::POSITION_OUTSIDE,
                12,
                6,
                10,
            ),
            array(
                array(
                    array(20, 100, 100, 20),
                    array(20, 80, 100, 20),
                ),
                BasicList::POSITION_INSIDE,
                12,
                6,
                10,
            ),
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
}