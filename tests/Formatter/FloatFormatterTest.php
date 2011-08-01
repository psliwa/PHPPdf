<?php

use PHPPdf\Formatter\FloatFormatter,
    PHPPdf\Util\Boundary,
    PHPPdf\Formatter\Chain,
    PHPPdf\Document,
    PHPPdf\Glyph\Glyph,
    PHPPdf\Glyph\Page;

class FloatFormatterTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PHPPdf\Formatter\FloatFormatter
     */
    private $formatter = null;
    private $document;

    public function setUp()
    {
        $this->formatter = new FloatFormatter();
        $this->document = new Document();
    }

    /**
     * @test
     */
    public function correctGlyphsPositionIfHasFloatSetToLeft()
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
        
        $container = $this->getGlyphMock($args[0][0], $args[0][1], $args[0][2], $args[0][3], array('getChildren'));

        $children = array();

        for($i=1; $i<$numArgs; $i+=2)
        {
            $children[] = $this->getGlyphMockWithFloatAndParent($args[$i][0], $args[$i][1], $args[$i][2], $args[$i][3], $args[$i+1], $container);

        }

        $container->expects($this->atLeastOnce())
                  ->method('getChildren')
                  ->will($this->returnValue($children));
        
        return array_merge(array($container), $children);
    }


    private function getGlyphMock($x, $y, $width, $height, array $methods = array(), $boundaryAtLeastOnce = true, $class = 'PHPPdf\Glyph\Container')
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
    public function correctGlyphPositionIfHasFloatSetToRight()
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
    public function correctGlyphsPositionWithNoFloatIfPreviousSiblingsHaveFloat()
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
    public function glyphsHaveEqualTopYCoordEvenIfHaveHeightIsDifferent()
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

    private function getGlyphMockWithFloatAndParent($x, $y, $width, $height, $float, $parent, array $methods = array())
    {
        $methods[] = 'getFloat';
        $methods[] = 'getParent';
        $glyph = $this->getGlyphMock($x, $y, $width, $height, $methods);

        $glyph->expects($this->atLeastOnce())
              ->method('getFloat')
              ->will($this->returnValue($float));

        $glyph->expects($this->atLeastOnce())
              ->method('getParent')
              ->will($this->returnValue($parent));

        return $glyph;
    }

    /**
     * @test
     */
    public function correctGlyphsPositionWithRightFloatIfRightPaddingIsSet()
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
        $container = $this->getGlyphMock(0, 500, 100, 100, array('getChildren', 'setHeight', 'getAttributesSnapshot'), false);

        $container->expects($this->once())
                  ->method('setHeight')
                  ->with(60)
                  ->will($this->returnValue($container));

        $children[] = $this->getGlyphMockWithFloatAndParent(0, 500, 40, 60, 'left', $container);
        $children[] = $this->getGlyphMockWithFloatAndParent(0, 440, 40, 40, 'right', $container);

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
        $container = $this->getGlyphMock(0, 500, 100, 100, array('getChildren', 'setHeight'), false);
        $container->expects($this->never())
                  ->method('setHeight');

        $child = $this->getGlyphMockWithFloatAndParent(0, 500, 100, 50, 'left', $container);

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
        $container = $this->getGlyphMock(0, 500, 100, 500, array('getChildren', 'setHeight'), false);
        
        $containerLeftFloated = $this->getGlyphMockWithFloatAndParent(0, 500, 10, 200, 'left', $container);
        $containerRightFloated = $this->getGlyphMockWithFloatAndParent(0, 300, 10, 100, 'right', $container);
        $containerWithoutFloat = $this->getGlyphMockWithFloatAndParent(0, 200, 10, 100, 'none', $container);
        
        $container->expects($this->atLeastOnce())
                  ->method('getChildren')
                  ->will($this->returnValue(array($containerLeftFloated, $containerRightFloated, $containerWithoutFloat)));
                  
        $this->formatter->format($container, $this->document);
        
        $this->assertEquals($containerLeftFloated->getDiagonalPoint()->getY(), $containerWithoutFloat->getFirstPoint()->getY());
    }
    
    /**
     * @test
     */
    public function moveGlyphWithRightFloatUnderPreviousSiblingIfPreviousSiblingHasLeftFloatAndBothElementsDontFit()
    {
        $container = $this->getGlyphMock(0, 500, 500, 500, array('getChildren', 'setHeight'), false);
        
        $containerLeftFloated = $this->getGlyphMockWithFloatAndParent(0, 500, 300, 200, 'left', $container);
        $containerRightFloated = $this->getGlyphMockWithFloatAndParent(0, 300, 300, 100, 'right', $container);
        
        $container->expects($this->atLeastOnce())
                  ->method('getChildren')
                  ->will($this->returnValue(array($containerLeftFloated, $containerRightFloated)));
                  
        $this->formatter->format($container, $this->document);
        
        $this->assertEquals(0, $containerLeftFloated->getFirstPoint()->getX());
        $this->assertEquals(200, $containerRightFloated->getFirstPoint()->getX());
        $this->assertEquals(500, $containerLeftFloated->getFirstPoint()->getY());
        $this->assertEquals(300, $containerRightFloated->getFirstPoint()->getY());
    }
    
    /**
     * @test
     * @dataProvider floatAndMarginProvider
     */
    public function marginIsRespectedEvenIfGlyphHasFloat($float, $marginLeft, $marginRight, $parentWidth, $width, $expectedXCoord)
    {
        $container = $this->getGlyphMock(0, 500, $parentWidth, 500, array('getChildren'), false);
        $containerFloated = $this->getGlyphMockWithFloatAndParent(0, 500, $width, 200, $float, $container);
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
            array(Glyph::FLOAT_LEFT, 100, 0, 500, 100, 100),
            array(Glyph::FLOAT_RIGHT, 100, 100, 500, 100, 300),
            array(Glyph::FLOAT_RIGHT, 0, 0, 500, 100, 400),
            array(Glyph::FLOAT_LEFT, 0, 0, 500, 100, 0),
        );
    }
}