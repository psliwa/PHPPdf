<?php

namespace PHPPdf\Test\Node;

use PHPPdf\Node\BasicListItem;

class BasicListItemTest extends \TestCase
{
    private $item;
    private $objectMother;
    
    public function setUp()
    {
        $this->item = new BasicListItem();
        $this->objectMother = new \GenericNodeObjectMother($this);
    }
    
    /**
     * @test
     * @dataProvider splitIfItemHasDescendantsLeafProvider
     */
    public function splitIfItemHasDescendantsLeaf($splitHeight, array $isLeafs, array $isAbleToExistsAboveCoords, $expected)
    {
        $height = 500;
        $width = 200;

        $boundary = $this->objectMother->getBoundaryStub(0, $height, $width, $height);
        $this->item->setHeight($height);
        $this->item->setWidth($width);
        $this->invokeMethod($this->item, 'setBoundary', array($boundary));
        
        foreach($isLeafs as $i => $isLeaf)
        {
            $node = $this->getMockBuilder('PHPPdf\Node\Node')
                          ->setMethods(array('isLeaf', 'hasLeafDescendants', 'isAbleToExistsAboveCoord', 'split'))
                          ->getMock();
            $node->expects($this->any())
                  ->method('isLeaf')
                  ->will($this->returnValue($isLeaf));
            $node->expects($this->any())
                  ->method('hasLeafDescendants')
                  ->will($this->returnValue($isLeaf));
            $node->expects($this->any())
                  ->method('isAbleToExistsAboveCoord')
                  ->with($height - $splitHeight)
                  ->will($this->returnValue($isAbleToExistsAboveCoords[$i]));
                  
            $boundary = $this->objectMother->getBoundaryStub(0, $height, 0, 200);
            $this->invokeMethod($node, 'setBoundary', array($boundary));
                  
            $this->item->add($node);
        }

        $productOfSplit = $this->item->split($splitHeight);
        $this->assertEquals($expected, $productOfSplit !== null);
    }
    
    public function splitIfItemHasDescendantsLeafProvider()
    {
        return array(
            array(10, array(false, true), array(true, false), false),
            array(210, array(false, true), array(true, true), true),
        );
    }
}