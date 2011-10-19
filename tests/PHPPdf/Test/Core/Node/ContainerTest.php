<?php

namespace PHPPdf\Test\Core\Node;

use PHPPdf\ObjectMother\NodeObjectMother;
use PHPPdf\Core\Document,
    PHPPdf\Core\Node\Container,
    PHPPdf\Core\Point,
    PHPPdf\Core\Node\Node;

class ContainerTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPPdf\Core\Node\Container
     */
    private $node;
    private $objectMother;

    public function setUp()
    {
        $this->node = new Container();
        $this->objectMother = new NodeObjectMother($this);
    }

    /**
     * @test
     */
    public function breakAt()
    {
        $this->node->setWidth(350)
                    ->setHeight(300);

        $boundary = $this->node->getBoundary();
        $boundary->setNext(50, 600)
                 ->setNext(400, 600)
                 ->setNext(400, 300)
                 ->setNext(50, 300)
                 ->close();

        $child1 = new Container();
        $child1->setWidth(350)
               ->setHeight(100);
        $boundary = $child1->getBoundary();
        $boundary->setNext(50, 600)
                 ->setNext(400, 600)
                 ->setNext(400, 500)
                 ->setNext(50, 500)
                 ->close();

        $this->node->add($child1);

        $child2 = $child1->copy();
        foreach($boundary as $point)
        {
            $child2->getBoundary()->setNext($point);
        }
        $child2->translate(0, 200);
        $this->node->add($child2);

        $result = $this->node->breakAt(250);

        $this->assertEquals(250, $this->node->getHeight());
        $children = $this->node->getChildren();
        $this->assertEquals(2, count($children));
        $this->assertEquals(100, $children[0]->getHeight());
        $this->assertEquals(50, $children[1]->getHeight());
        $this->assertEquals(array(400, 350), $children[1]->getEndDrawingPoint());

        $this->assertEquals(50, $result->getHeight());
        $children = $result->getChildren();
        $this->assertEquals(1, count($children));
        $this->assertEquals(50, $children[0]->getHeight());
        $this->assertEquals(array(50, 350), $children[0]->getStartDrawingPoint());
        $this->assertEquals(array(400, 300), $children[0]->getEndDrawingPoint());
    }

    /**
     * @test
     * @dataProvider widthProvider
     */
    public function minWidthIsMaxValueOfMinWidthOfChildren(array $childrenMinWidths, $paddingLeft = 0, $paddingRight = 0, $marginLeft = 0, $marginRight = 0)
    {
        $children = array();
        foreach($childrenMinWidths as $minWidth)
        {
            $child = $this->getMock('PHPPdf\Core\Node\Container', array('getMinWidth'));
            $child->expects($this->atLeastOnce())
                  ->method('getMinWidth')
                  ->will($this->returnValue($minWidth));
            $this->node->add($child);
        }

        $this->node->setAttribute('padding-left', $paddingLeft);
        $this->node->setAttribute('padding-right', $paddingRight);
        $this->node->setAttribute('margin-left', $marginLeft);
        $this->node->setAttribute('margin-right', $marginRight);
        $minWidth = max($childrenMinWidths) + $paddingLeft + $paddingRight + $marginLeft + $marginRight;

        $this->assertEquals($minWidth, $this->node->getMinWidth());
    }

    public function widthProvider()
    {
        return array(
            array(
                array(10, 20, 30),
            ),
            array(
                array(10, 20, 30), 20, 10
            ),
            array(
                array(10, 20, 30), 20, 10, 3, 2
            ),
        );
    }

    /**
     * @test
     * @dataProvider resizeDataProvider
     */
    public function resizeCausesBoundaryPointsTranslations($horizontalResizeBy, $verticalResizeBy, $width, $childWidth, $paddingRight, $childMarginRight, $childRelativeWidth = null)
    {
        $boundary = $this->createResizableBoundaryMock($width, $horizontalResizeBy, $verticalResizeBy, 2);

        if($childRelativeWidth !== null)
        {
            $relativeWidth = ((int) $childRelativeWidth)/100;
            $childResizeBy = ($width + $horizontalResizeBy) * $relativeWidth - $childWidth;
        }
        else
        {
            $childResizeBy = $horizontalResizeBy + ($width - $paddingRight - ($childWidth + $childMarginRight));
            $childResizeBy = $childResizeBy < 0 ? $childResizeBy : 0;
        }

        $childBoundary = $this->createResizableBoundaryMock($childWidth, $childResizeBy, 0, $childResizeBy != 0 ? 4 : false);

        $child = new Container();
        $child->setAttribute('margin-right', $childMarginRight);
        $child->setRelativeWidth($childRelativeWidth);

        $this->node->add($child);
        $this->node->setAttribute('padding-right', $paddingRight);

        $this->invokeMethod($this->node, 'setBoundary', array($boundary));
        $this->invokeMethod($child, 'setBoundary', array($childBoundary));

        $this->node->resize($horizontalResizeBy, $verticalResizeBy);
    }

    public function resizeDataProvider()
    {
        return array(
            array(
                -10, 0, 100, 100, 0, 0
            ),
            array(
                -10, 0, 100, 95, 0, 0
            ),
            array(
                -10, 10, 100, 95, 1, 1
            ),
            array(
                -10, -10, 100, 90, 0, 0
            ),
            array(
                -10, 0, 100, 85, 0, 0
            ),
            array(
                10, 0, 100, 100, 0, 0, '100%'
            ),
        );
    }

    public function createResizableBoundaryMock($width, $horizontalResizeBy, $verticalResizeBy, $initSequence = 1)
    {
        $boundary = $this->getMock('PHPPdf\Core\Boundary', array('pointTranslate', 'getDiagonalPoint', 'getFirstPoint'));

        $boundary->expects($this->atLeastOnce())
                 ->method('getDiagonalPoint')
                 ->will($this->returnValue(Point::getInstance($width, 0)));
        $boundary->expects($this->any())
                 ->method('getFirstPoint')
                 ->will($this->returnValue(Point::getInstance(0, 0)));

        if($initSequence !== false)
        {
            $boundary->expects($this->at($initSequence++))
                     ->method('pointTranslate')
                     ->with(1, $horizontalResizeBy, 0);
            $boundary->expects($this->at($initSequence++))
                     ->method('pointTranslate')
                     ->with(2, $horizontalResizeBy, $verticalResizeBy);
            $boundary->expects($this->at($initSequence++))
                     ->method('pointTranslate')
                     ->with(3, 0, $verticalResizeBy);
        }
        else
        {
            $boundary->expects($this->never())
                     ->method('pointTranslate');
        }


        return $boundary;
    }

    /**
     * @test
     */
    public function priorityOfChildIsGreaterByOneOfPriorityOfParent()
    {
        $child = new Container();
        $this->node->add($child);

        $this->assertLessThan($this->node->getPriority(), $child->getPriority());

        $superParentContainer = new Container();
        $superParentContainer->add($this->node);

        $this->assertLessThan($superParentContainer->getPriority(), $this->node->getPriority());
        $this->assertLessThan($this->node->getPriority(), $child->getPriority());
    }
    
    /**
     * @test
     */
    public function graphicContextIfFetchedFromPage()
    {
        $graphicContextStub = 'some stub';
        
        $pageMock = $this->getMock('PHPPdf\Core\Node\Page', array('getGraphicsContext'));
        
        $pageMock->expects($this->once())
                 ->method('getGraphicsContext')
                 ->will($this->returnValue($graphicContextStub));
                 
         $this->node->setParent($pageMock);
         
         $this->assertEquals($graphicContextStub, $this->node->getGraphicsContext());
    }
    
    /**
     * @test
     */
    public function hasLeafDescendants()
    {
        $this->assertFalse($this->node->hasLeafDescendants());

        $child = new Container();
        $this->node->add($child);
        
        $this->assertFalse($this->node->hasLeafDescendants());
        
        $leaf = $this->getMockBuilder('PHPPdf\Core\Node\Node')
                     ->setMethods(array('hasLeafDescendants'))
                     ->getMock();
        $leaf->expects($this->atLeastOnce())
             ->method('hasLeafDescendants')
             ->will($this->returnValue(true));
             
        $child->add($leaf);

        $this->assertTrue($this->node->hasLeafDescendants());
    }
    
    /**
     * @test
     */
    public function childrenWithRelativeWidthWillBePropelyHorizontallyResized()
    {
        $width = 100;
        $horizontalResize = 50;
        
        for($i=0; $i<2; $i++)
        {
            $child = new Container();
            $child->setWidth(50);
            $child->setRelativeWidth('50%');
            
            $boundary = $this->objectMother->getBoundaryStub(50*$i, 50, 50, 50);
            $this->invokeMethod($child, 'setBoundary', array($boundary));
            
            $this->node->add($child);
        }
        
        $boundary = $this->objectMother->getBoundaryStub(0, 50, $width, 50);
        $this->invokeMethod($this->node, 'setBoundary', array($boundary));
        
        $this->node->resize($horizontalResize, 0);
        
        $children = $this->node->getChildren();
        
        $expectedWidth = ($width + $horizontalResize)/2;
        foreach($children as $child)
        {
            $realWidth = $child->getDiagonalPoint()->getX() - $child->getFirstPoint()->getX();
            $this->assertEquals($expectedWidth, $realWidth);
        }
    }
    
    /**
     * @test
     */
    public function moveButNotResizeChildWithRightFloatOnResizing()
    {
        $width = 100;
        $height = 100;
        $this->node->setWidth($width);
        $this->node->setHeight($height);

        $boundary = $this->objectMother->getBoundaryStub(0, $height, $width, $height);
        
        $this->invokeMethod($this->node, 'setBoundary', array($boundary));
        
        $childWidth = 50;
        $childHeight = 100;
        
        $child = new Container(array(
        	'width' => $childWidth, 
        	'height' => $childHeight, 
        	'float' => 'right'
        ));
        
        $childBoundary = $this->objectMother->getBoundaryStub($width - $childWidth, $childHeight, $childWidth, $childHeight);
        $this->invokeMethod($child, 'setBoundary', array($childBoundary));
        
        $this->node->add($child);
        
        $xResize = 20;
        
        $this->node->resize($xResize, 0);
        
        $expectedChildDiagonalXCoord = $width + $xResize;
        $this->assertEquals($expectedChildDiagonalXCoord, $child->getDiagonalPoint()->getX());
        $this->assertEquals($childWidth, $child->getWidth());
        
        $xResize = -40;
        
        $this->node->resize($xResize, 0);
        
        $expectedChildDiagonalXCoord = $expectedChildDiagonalXCoord + $xResize;
        $this->assertEquals($expectedChildDiagonalXCoord, $child->getDiagonalPoint()->getX());
        $this->assertEquals($childWidth, $child->getWidth());
    }
}