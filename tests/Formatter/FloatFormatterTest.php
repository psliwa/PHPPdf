<?php

use PHPPdf\Formatter\FloatFormatter,
    PHPPdf\Util\Boundary,
    PHPPdf\Formatter\Chain,
    PHPPdf\Document,
    PHPPdf\Glyph\Page;

class FloatFormatterTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PHPPdf\Formatter\FloatFormatter
     */
    private $formatter = null;

    public function setUp()
    {
        $this->formatter = new FloatFormatter(new Document());
    }

    /**
     * @test
     */
    public function leftFloatPositionCorrection()
    {
        $glyph1 = $this->getGlyphMock(0, 700, 300, 300);
        $glyph2 = $this->getGlyphMock(0, 400, 200, 200);

        $glyph1->setFloat('left');
        $glyph2->setFloat('left');

        $page = new Page(array('page-size' => '700:700:'));
        $page->add($glyph1)
             ->add($glyph2);

        $this->formatter->preFormat($page);
        $this->formatter->postFormat($page);

        $this->assertEquals(array(300, 700), $glyph2->getStartDrawingPoint());
        $this->assertEquals(array(500, 500), $glyph2->getEndDrawingPoint());
    }

    /**
     * @test
     */
    public function rightFloatPositionCorrection()
    {
        $glyph1 = $this->getGlyphMock(0, 500, 300, 300);
        $glyph2 = $this->getGlyphMock(0, 200, 200, 200);

        $glyph1->setFloat('left');
        $glyph2->setFloat('right');
        
        $page = new Page(array('page-size' => '700:500:'));
        $page->add($glyph1)
             ->add($glyph2);

        $this->formatter->preFormat($page);
        $this->formatter->postFormat($page);

        $this->assertEquals(array(500, 500), $glyph2->getStartDrawingPoint());
        $this->assertEquals(array(700, 300), $glyph2->getEndDrawingPoint());
    }

    /**
     * @test
     */
    public function noneFloatPositionCorrection()
    {
        $glyph1 = $this->getGlyphMock(0, 700, 300, 300);
        $glyph2 = $this->getGlyphMock(0, 400, 200, 200);
        $glyph3 = $this->getGlyphMock(0, 200, 100, 100);

        $glyph1->setFloat('left');
        $glyph2->setFloat('right');
        $glyph3->setFloat('none');

        $page = new Page(array('page-size' => '700:700:'));
        $page->add($glyph1)
             ->add($glyph2)
             ->add($glyph3);


        $this->formatter->preFormat($page);
        $this->formatter->postFormat($page);

        $this->assertEquals(array(500, 700), $glyph2->getStartDrawingPoint());
        $this->assertEquals(array(700, 500), $glyph2->getEndDrawingPoint());

        $this->assertEquals(array(0, 500), $glyph3->getStartDrawingPoint());
    }

    private function getGlyphMock($x, $y, $width, $height, array $methods = array(), $boundaryAtLeastOnce = true)
    {
        $methods = array_merge(array('getBoundary', 'getHeight', 'getWidth'), $methods);
        $mock = $this->getMock('PHPPdf\Glyph\Container', $methods);

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
    public function parentDimensionCorrectionWithFloatInFewRows()
    {
        $container = $this->getGlyphMock(0, 500, 500, 100, array('getChildren', 'getParent'));

        $children = array();
        $glyph = $this->getGlyphMock(0, 500, 20, 20, array('getParent'));
        $glyph->setFloat('left');
        $children[] = $glyph;
        
        $glyph = $this->getGlyphMock(0, 480, 20, 20, array('getParent'));
        $glyph->setFloat('left');
        $children[] = $glyph;

        $glyph = $this->getGlyphMock(0, 460, 20, 20, array('getParent'));
        $children[] = $glyph;

        $glyph = $this->getGlyphMock(0, 440, 20, 20, array('getParent'));
        $glyph->setFloat('left');
        $children[] = $glyph;

        $glyph = $this->getGlyphMock(0, 420, 20, 20, array('getParent'));
        $glyph->setFloat('left');
        $children[] = $glyph;

        foreach($children as $child)
        {
            $child->expects($this->atLeastOnce())
                  ->method('getParent')
                  ->will($this->returnValue($container));
        }

        $container->expects($this->atLeastOnce())
                  ->method('getChildren')
                  ->will($this->returnValue($children));

        $this->formatter->preFormat($container);
        $this->formatter->postFormat($container);

        $boundary = $container->getBoundary();

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
        $container = $this->getGlyphMock(0, 500, 100, 40, array('getChildren', 'getParent'));

        $children = array();
        $glyph = $this->getGlyphMock(0, 500, 80, 20, array('getParent'));
        $glyph->setFloat($float);
        $children[] = $glyph;
        
        $glyph = $this->getGlyphMock(0, 480, 80, 20, array('getParent'));
        $glyph->setFloat($float);
        $children[] = $glyph;

        foreach($children as $child)
        {
            $child->expects($this->atLeastOnce())
                  ->method('getParent')
                  ->will($this->returnValue($container));
        }
        
        $container->expects($this->atLeastOnce())
                  ->method('getChildren')
                  ->will($this->returnValue($children));

        $this->formatter->preFormat($container);
        $this->formatter->postFormat($container);

        $boundary = $container->getBoundary();
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
    public function glyphsWithVerticalPaddings()
    {
        $container = $this->getGlyphMock(0, 500, 100, 40, array('getChildren', 'getParent'));

        $children = array();
        $glyph1 = $this->getGlyphMockWithFloatAndParent(0, 500, 80, 20, 'left', $container);
        $glyph1->setPaddingTop(7);
        $glyph1->setMarginBottom(15);
        $children[] = $glyph1;

        $glyph2 = $this->getGlyphMockWithFloatAndParent(0, 480, 80, 20, 'right', $container);
        $glyph2->setPaddingTop(10);
        $glyph2->setMarginBottom(15);
        $children[] = $glyph2;

        $container->expects($this->atLeastOnce())
                  ->method('getChildren')
                  ->will($this->returnValue($children));

        $this->formatter->preFormat($container);
        $this->formatter->postFormat($container);

        $this->assertEquals($glyph1->getBoundary()->getFirstPoint()->getY(), $glyph2->getBoundary()->getFirstPoint()->getY());
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
    public function rightFloatingWithRightPadding()
    {
        $container = $this->getGlyphMock(0, 500, 100, 40, array('getChildren', 'getParent'));

        $glyph = $this->getGlyphMockWithFloatAndParent(0, 500, 80, 20, 'right', $container);
        $glyph->setPaddingRight(20);

        $container->expects($this->atLeastOnce())
                  ->method('getChildren')
                  ->will($this->returnValue(array($glyph)));

        $this->formatter->preFormat($container);
        $this->formatter->postFormat($container);

        $this->assertEquals($container->getBoundary()->getDiagonalPoint()->getX(), $glyph->getBoundary()->getDiagonalPoint()->getX());
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

        $this->formatter->preFormat($container);
        $this->formatter->postFormat($container);
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

        $this->formatter->preFormat($container);
        $this->formatter->postFormat($container);
    }
}