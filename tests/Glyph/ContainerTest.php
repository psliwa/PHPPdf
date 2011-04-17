<?php

use PHPPdf\Document,
    PHPPdf\Glyph\Container,
    PHPPdf\Util\Point,
    PHPPdf\Glyph\AbstractGlyph;

class ContainerTest extends TestCase
{
    /**
     * @var \PHPPdf\Glyph\Container
     */
    private $glyph;

    public function setUp()
    {
        $this->glyph = new Container();
    }

    /**
     * @test
     */
    public function split()
    {
        $this->glyph->setWidth(350)
                    ->setHeight(300);

        $boundary = $this->glyph->getBoundary();
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

        $this->glyph->add($child1);

        $child2 = $child1->copy();
        foreach($boundary as $point)
        {
            $child2->getBoundary()->setNext($point);
        }
        $child2->translate(0, 200);
        $this->glyph->add($child2);

        $result = $this->glyph->split(250);

        $this->assertEquals(250, $this->glyph->getHeight());
        $children = $this->glyph->getChildren();
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
    public function minWidthIsMaxValueOfMinWidthOfChildren(array $childrenMinWidths, $paddingLeft = 0, $paddingRight = 0)
    {
        $children = array();
        foreach($childrenMinWidths as $minWidth)
        {
            $child = $this->getMock('PHPPdf\Glyph\Container', array('getMinWidth'));
            $child->expects($this->atLeastOnce())
                  ->method('getMinWidth')
                  ->will($this->returnValue($minWidth));
            $this->glyph->add($child);
        }

        $this->glyph->setPaddingLeft($paddingLeft);
        $this->glyph->setPaddingRight($paddingRight);
        $minWidth = max($childrenMinWidths) + $paddingLeft + $paddingRight;

        $this->assertEquals($minWidth, $this->glyph->getMinWidth());
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
        );
    }

    /**
     * @test
     * @dataProvider resizeDataProvider
     */
    public function widthReduceTranslateBoundaryAndChildrenGlyphIfNecessary($resizeBy, $width, $childWidth, $paddingRight, $childMarginRight)
    {
        $boundary = $this->createResizableBoundaryMock($width, $resizeBy);

        $childResizeBy = $resizeBy + ($width- $paddingRight - ($childWidth + $childMarginRight));
        $childBoundary = $this->createResizableBoundaryMock($childWidth, $childResizeBy < 0 ? $childResizeBy : 0, 2);

        $child = new Container();
        $child->setAttribute('margin-right', $childMarginRight);
        $this->glyph->add($child);
        $this->glyph->setAttribute('padding-right', $paddingRight);

        $this->invokeMethod($this->glyph, 'setBoundary', array($boundary));
        $this->invokeMethod($child, 'setBoundary', array($childBoundary));

        $this->glyph->resize($resizeBy);
    }

    public function resizeDataProvider()
    {
        return array(
            array(
                -10, 100, 100, 0, 0
            ),
            array(
                -10, 100, 95, 0, 0
            ),
            array(
                -10, 100, 95, 1, 1
            ),
            array(
                -10, 100, 90, 0, 0
            ),
            array(
                -10, 100, 85, 0, 0
            ),
        );
    }

    public function createResizableBoundaryMock($width, $resizeBy, $initSequence = 1)
    {
        $boundary = $this->getMock('PHPPdf\Util\Boundary', array('pointTranslate', 'getDiagonalPoint'));

        $boundary->expects($this->atLeastOnce())
                 ->method('getDiagonalPoint')
                 ->will($this->returnValue(Point::getInstance($width, 0)));

        if($resizeBy == 0)
        {
            $boundary->expects($this->never())
                     ->method('pointTranslate');
        }
        else
        {
            $boundary->expects($this->at($initSequence))
                     ->method('pointTranslate')
                     ->with(1, $resizeBy, 0);
            $boundary->expects($this->at($initSequence+1))
                     ->method('pointTranslate')
                     ->with(2, $resizeBy, 0);
        }

        return $boundary;
    }
}