<?php

namespace PHPPdf\Test\Core\Node;

use PHPPdf\Core\Node\BasicListItem;
use PHPPdf\ObjectMother\NodeObjectMother;

class BasicListItemTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $item;
    private $objectMother;
    
    public function setUp()
    {
        $this->item = new BasicListItem();
        $this->objectMother = new NodeObjectMother($this);
    }
    
    /**
     * @test
     * @dataProvider breakIfItemHasDescendantsLeafProvider
     */
    public function breakIfItemHasDescendantsLeaf($breakingHeight, array $isLeafs, array $isAbleToExistsAboveCoords, $expected)
    {
        $height = 500;
        $width = 200;

        $boundary = $this->objectMother->getBoundaryStub(0, $height, $width, $height);
        $this->item->setHeight($height);
        $this->item->setWidth($width);
        $this->invokeMethod($this->item, 'setBoundary', array($boundary));
        
        foreach($isLeafs as $i => $isLeaf)
        {
            $node = $this->getMockBuilder('PHPPdf\Core\Node\Node')
                          ->setMethods(array('isLeaf', 'hasLeafDescendants', 'isAbleToExistsAboveCoord', 'breakAt'))
                          ->getMock();
            $node->expects($this->any())
                  ->method('isLeaf')
                  ->will($this->returnValue($isLeaf));
            $node->expects($this->any())
                  ->method('hasLeafDescendants')
                  ->will($this->returnValue($isLeaf));
            $node->expects($this->any())
                  ->method('isAbleToExistsAboveCoord')
                  ->with($height - $breakingHeight)
                  ->will($this->returnValue($isAbleToExistsAboveCoords[$i]));
                  
            $boundary = $this->objectMother->getBoundaryStub(0, $height, 0, 200);
            $this->invokeMethod($node, 'setBoundary', array($boundary));
                  
            $this->item->add($node);
        }

        $productOfBreaking = $this->item->breakAt($breakingHeight);
        $this->assertEquals($expected, $productOfBreaking !== null);
    }
    
    public function breakIfItemHasDescendantsLeafProvider()
    {
        return array(
            array(10, array(false, true), array(true, false), false),
            array(210, array(false, true), array(true, true), true),
        );
    }
}