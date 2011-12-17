<?php

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Formatter\FloatFormatter,
    PHPPdf\Core\Boundary,
    PHPPdf\Core\Formatter\Chain,
    PHPPdf\Core\Document,
    PHPPdf\Core\Node\Node,
    PHPPdf\Core\Node\Page;

class FloatFormatterTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    /**
     * @var PHPPdf\Core\Formatter\FloatFormatter
     */
    private $formatter = null;
    private $document;

    public function setUp()
    {
        $this->formatter = new FloatFormatter();        
        $this->document = $this->createDocumentStub();
    }

    /**
     * @test
     */
    public function correctNodesPositionIfHasFloatSetToLeft()
    {
        $containers = $this->createContainerWithFloatingChildren(
                array(0, 700, 700, 700),
                array(0, 700, 300, 300), 'left',
                array(0, 400, 200, 200), 'left'
        );

        $this->formatter->format($containers[0], $this->document);

        $this->assertEquals(array(300, 700), $containers[2]->getStartDrawingPoint());
        $this->assertEquals(array(500, 500), $containers[2]->getEndDrawingPoint());
    }

    private function createContainerWithFloatingChildren()
    {
        $args = func_get_args();
        $numArgs = func_num_args();
        
        $container = $this->getNodeMock($args[0][0], $args[0][1], $args[0][2], $args[0][3], array('getChildren'));

        $children = array();

        for($i=1; $i<$numArgs; $i+=2)
        {
            $children[] = $this->getNodeMockWithFloatAndParent($args[$i][0], $args[$i][1], $args[$i][2], $args[$i][3], $args[$i+1], $container);

        }

        $container->expects($this->atLeastOnce())
                  ->method('getChildren')
                  ->will($this->returnValue($children));
        
        return array_merge(array($container), $children);
    }


    private function getNodeMock($x, $y, $width, $height, array $methods = array(), $boundaryAtLeastOnce = true, $class = 'PHPPdf\Core\Node\Container')
    {
        $methods = array_merge(array('getBoundary', 'getHeight', 'getWidth'), $methods);
        $mock = $this->getMock($class, $methods);

        $boundary = new Boundary();
        $boundary->setNext($x, $y)
                 ->setNext($x+$width, $y)
                 ->setNext($x+$width, $y-$height)
                 ->setNext($x, $y-$height)
                 ->close();

        $mock->expects($boundaryAtLeastOnce ? $this->atLeastOnce() : $this->any())
             ->method('getBoundary')
             ->will($this->returnValue($boundary));

        $mock->expects($this->any())
             ->method('getHeight')
             ->will($this->returnValue($height));

        $mock->expects($this->any())
             ->method('getWidth')
             ->will($this->returnValue($width));

        return $mock;
    }

    /**
     * @test
     */
    public function correctNodePositionIfHasFloatSetToRight()
    {
        $containers = $this->createContainerWithFloatingChildren(
                array(0, 700, 700, 700),
                array(0, 500, 300, 300), 'left',
                array(0, 200, 200, 200), 'right'
        );

        $this->formatter->format($containers[0], $this->document);

        $this->assertEquals(array(500, 500), $containers[2]->getStartDrawingPoint());
        $this->assertEquals(array(700, 300), $containers[2]->getEndDrawingPoint());
    }

    /**
     * @test
     */
    public function correctNodesPositionWithNoFloatIfPreviousSiblingsHaveFloat()
    {
        $containers = $this->createContainerWithFloatingChildren(
                array(0, 700, 700, 700),
                array(0, 700, 300, 300), 'left',
                array(0, 400, 200, 200), 'right',
                array(0, 200, 100, 100), 'none'
        );

        $this->formatter->format($containers[0], $this->document);

        $this->assertEquals(array(500, 700), $containers[2]->getStartDrawingPoint());
        $this->assertEquals(array(700, 500), $containers[2]->getEndDrawingPoint());

        $this->assertEquals(array(0, 400), $containers[3]->getStartDrawingPoint());
    }

    /**
     * @test
     */
    public function correctParentDimensionIfHaveSomeFloatingChildrenInFewRows()
    {
        $containers = $this->createContainerWithFloatingChildren(
                array(0, 500, 500, 100),
                array(0, 500, 20, 20), 'left',
                array(0, 480, 20, 20), 'left',
                array(0, 460, 20, 20), 'none',
                array(0, 440, 20, 20), 'left',
                array(0, 420, 20, 20), 'left'
        );

        $this->formatter->format($containers[0], $this->document);

        $boundary = $containers[0]->getBoundary();

        $this->assertEquals(array(0, 500), $boundary[0]->toArray());
        $this->assertEquals(array(500, 500), $boundary[1]->toArray());
        $this->assertEquals(array(500, 440), $boundary[2]->toArray());
    }

    /**
     * @test
     * @dataProvider flowTypes
     */
    public function parentOverflowWhileFloating($float)
    {
        $containers = $this->createContainerWithFloatingChildren(
                array(0, 500, 100, 40),
                array(0, 500, 80, 20), $float,
                array(0, 480, 80, 20), $float
        );

        $this->formatter->format($containers[0], $this->document);

        $boundary = $containers[0]->getBoundary();
        $this->assertEquals(array(0, 500), $boundary[0]->toArray());
        $this->assertEquals(array(100, 460), $boundary[2]->toArray());
    }

    public function flowTypes()
    {
        return array(
            array('right'),
            array('left'),
        );
    }

    /**
     * @test
     */
    public function nodesHaveEqualTopYCoordEvenIfHaveHeightIsDifferent()
    {
        $containers = $this->createContainerWithFloatingChildren(
                array(0, 500, 200, 40),
                array(0, 500, 80, 20), 'left',
                array(0, 480, 80, 20), 'right'
        );

        $containers[1]->setAttribute('padding-top', 7);
        $containers[1]->setAttribute('margin-bottom', 15);

        $containers[2]->setAttribute('padding-top', 10);
        $containers[2]->setAttribute('margin-bottom', 15);

        $this->formatter->format($containers[0], $this->document);

        $this->assertEquals($containers[1]->getFirstPoint()->getY(), $containers[2]->getFirstPoint()->getY());
    }

    private function getNodeMockWithFloatAndParent($x, $y, $width, $height, $float, $parent, array $methods = array())
    {
        $methods[] = 'getFloat';
        $methods[] = 'getParent';
        $node = $this->getNodeMock($x, $y, $width, $height, $methods);

        $node->expects($this->atLeastOnce())
              ->method('getFloat')
              ->will($this->returnValue($float));

        $node->expects($this->any())
              ->method('getParent')
              ->will($this->returnValue($parent));

        //internally in Node class is used $parent propery (not getParent() method) due to performance
        $this->writeAttribute($node, 'parent', $parent);

        return $node;
    }

    /**
     * @test
     */
    public function correctNodesPositionWithRightFloatIfRightPaddingIsSet()
    {
        $containers = $this->createContainerWithFloatingChildren(
                array(0, 500, 100, 40),
                array(0, 500, 80, 20), 'right'
        );

        $containers[1]->setAttribute('padding-right', 20);

        $this->formatter->format($containers[0], $this->document);

        $this->assertEquals($containers[0]->getBoundary()->getDiagonalPoint()->getX(), $containers[1]->getBoundary()->getDiagonalPoint()->getX());
    }

    /**
     * @test
     */
    public function correctParentsHeightWhenFloatingChildrenHasDifferentHeight()
    {
        $container = $this->getNodeMock(0, 500, 100, 100, array('getChildren', 'setHeight', 'getAttributesSnapshot'), false);

        $container->expects($this->once())
                  ->method('setHeight')
                  ->with(60)
                  ->will($this->returnValue($container));

        $children[] = $this->getNodeMockWithFloatAndParent(0, 500, 40, 60, 'left', $container);
        $children[] = $this->getNodeMockWithFloatAndParent(0, 440, 40, 40, 'right', $container);

        $container->expects($this->atLeastOnce())
                  ->method('getChildren')
                  ->will($this->returnValue($children));

        $this->formatter->format($container, $this->document);
    }

    /**
     * @test
     */
    public function dontCorrectParentsHeightIfHisChildrenPositionHasNotBeenCorrected()
    {
        $container = $this->getNodeMock(0, 500, 100, 100, array('getChildren', 'setHeight'), false);
        $container->expects($this->never())
                  ->method('setHeight');

        $child = $this->getNodeMockWithFloatAndParent(0, 500, 100, 50, 'left', $container);

        $container->expects($this->atLeastOnce())
                  ->method('getChildren')
                  ->will($this->returnValue(array($child)));

        $this->formatter->format($container, $this->document);
    }
    
    /**
     * @test
     */
    public function containerWithoutFloatShouldBeBelowAllOfPreviousSiblingsWithFloat()
    {
        $container = $this->getNodeMock(0, 500, 100, 500, array('getChildren', 'setHeight'), false);
        
        $containerLeftFloated = $this->getNodeMockWithFloatAndParent(0, 500, 10, 200, 'left', $container);
        $containerRightFloated = $this->getNodeMockWithFloatAndParent(0, 300, 10, 100, 'right', $container);
        $containerWithoutFloat = $this->getNodeMockWithFloatAndParent(0, 200, 10, 100, 'none', $container);
        
        $container->expects($this->atLeastOnce())
                  ->method('getChildren')
                  ->will($this->returnValue(array($containerLeftFloated, $containerRightFloated, $containerWithoutFloat)));
                  
        $this->formatter->format($container, $this->document);
        
        $this->assertEquals($containerLeftFloated->getDiagonalPoint()->getY(), $containerWithoutFloat->getFirstPoint()->getY());
    }
    
    /**
     * @test
     * @dataProvider paddingProvider
     */
    public function moveNodeWithRightFloatUnderPreviousSiblingIfPreviousSiblingHasLeftFloatAndBothElementsDontFit($firstContainerFloat, $secondContainerFloat, $heightOfFirstContainer, $paddingTopOfSecondContainer)
    {
        $heightOfParentContainer = 500;
        $widthOfParentContainer = 500;
        $container = $this->getNodeMock(0, $heightOfParentContainer, $widthOfParentContainer, $heightOfParentContainer, array('getChildren', 'setHeight'), false);
        
        $widthOfFirstContainer = 300;
        $firstPointYCoordOfSecondContainer = $heightOfParentContainer - $heightOfFirstContainer;// - $paddingTopOfSecondContainer;
        $heightOfSecondContainer = 100;
        $widthOfSecondContainer = 300;
        
        $firstContainer = $this->getNodeMockWithFloatAndParent(0, $heightOfParentContainer, $widthOfFirstContainer, $heightOfFirstContainer, $firstContainerFloat, $container);
        $secondContainer = $this->getNodeMockWithFloatAndParent(0, $firstPointYCoordOfSecondContainer, $widthOfSecondContainer, $heightOfSecondContainer, $secondContainerFloat, $container);
        $secondContainer->setAttribute('padding-top', $paddingTopOfSecondContainer);
        
        $container->expects($this->atLeastOnce())
                  ->method('getChildren')
                  ->will($this->returnValue(array($firstContainer, $secondContainer)));
                  
        $this->formatter->format($container, $this->document);
        
        $expectedXCoordOfFirstContainer = $firstContainerFloat == Node::FLOAT_LEFT ? 0 : ($widthOfParentContainer - $widthOfSecondContainer);
        $this->assertEquals($expectedXCoordOfFirstContainer, $firstContainer->getFirstPoint()->getX());
        
        $expectedXCoordOfSecondContainer = $secondContainerFloat == Node::FLOAT_LEFT ? 0 : ($widthOfParentContainer - $widthOfSecondContainer);
        $this->assertEquals($expectedXCoordOfSecondContainer, $secondContainer->getFirstPoint()->getX());
        
        
        $this->assertEquals($heightOfParentContainer, $firstContainer->getFirstPoint()->getY());
        
        $expectedYCoordOfSecondContainer = $heightOfParentContainer - $heightOfFirstContainer;
        $this->assertEquals($expectedYCoordOfSecondContainer, $secondContainer->getFirstPoint()->getY());
    }
    
    public function paddingProvider()
    {
        return array(
            array('left', 'right', 200, 0),
            array('left', 'left', 200, 0),
            array('left', 'left', 200, 10),
            array('right', 'left', 200, 10),
            array('left', 'right', 200, 10),
            array('right', 'right', 200, 10),
        );
    }
    
    /**
     * @test
     * @dataProvider floatAndMarginProvider
     */
    public function marginIsRespectedEvenIfNodeHasFloat($float, $marginLeft, $marginRight, $parentWidth, $width, $expectedXCoord)
    {
        $container = $this->getNodeMock(0, 500, $parentWidth, 500, array('getChildren'), false);
        $containerFloated = $this->getNodeMockWithFloatAndParent(0, 500, $width, 200, $float, $container);
        $containerFloated->setAttribute('margin-left', $marginLeft);
        $containerFloated->setAttribute('margin-right', $marginRight);
        
        $container->expects($this->atLeastOnce())
                  ->method('getChildren')
                  ->will($this->returnValue(array($containerFloated)));
                  
        $this->formatter->format($container, $this->document);
        
        $this->assertEquals($expectedXCoord, $containerFloated->getFirstPoint()->getX());
    }
    
    public function floatAndMarginProvider()
    {
        return array(
            array(Node::FLOAT_LEFT, 100, 0, 500, 100, 100),
            array(Node::FLOAT_RIGHT, 100, 100, 500, 100, 300),
            array(Node::FLOAT_RIGHT, 0, 0, 500, 100, 400),
            array(Node::FLOAT_LEFT, 0, 0, 500, 100, 0),
        );
    }
}