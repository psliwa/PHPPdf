<?php

use PHPPdf\Document;
use PHPPdf\Util\Point;
use PHPPdf\Glyph\AbstractGlyph;
use PHPPdf\Glyph\Container;
use PHPPdf\Glyph\Page;
use PHPPdf\Formatter\StandardPositionFormatter;

class StandardPositionFormatterTest extends PHPUnit_Framework_TestCase
{
    private $formatter;

    public function setUp()
    {
        $this->formatter = new StandardPositionFormatter();
        $this->formatter->setDocument(new Document());
    }

    /**
     * @test
     */
    public function glyphWithoutSibling()
    {
        $mock = $this->getMock('\PHPPdf\Glyph\AbstractGlyph', array(
            'getPage',
            'setStartDrawingPoint',
            'getParent',
            'getPreviousSibling',
            'getWidth',
            'getHeight',
            'getLineHeight',
            'getLineSizes',
            'getStartDrawingPoint',
            'getBoundary',
        ));

        $page = new Page();

        $boundaryMock = $this->getMock('\PHPPdf\Util\Boundary', array(
            'setNext',
            'close',
            'getFirstPoint',
        ));
        $boundaryMock->expects($this->exactly(4))
                     ->method('setNext')
                     ->will($this->returnValue($boundaryMock));
        $boundaryMock->expects($this->once())
                     ->method('close');
        $boundaryMock->expects($this->atLeastOnce())
                     ->method('getFirstPoint')
                     ->will($this->returnValue(Point::getInstance(0, 800)));

        $mock->expects($this->once())
             ->method('getPage')
             ->will($this->returnValue($page));
        $mock->expects($this->exactly(2))
             ->method('getBoundary')
             ->will($this->returnValue($boundaryMock));
        $mock->expects($this->atLeastOnce())
             ->method('getParent')
             ->will($this->returnValue($page));
        $mock->expects($this->once())
             ->method('getPreviousSibling')
             ->will($this->returnValue(null));

        $this->formatter->preFormat($mock);
        $this->formatter->postFormat($mock);
    }

    /**
     * @test
     */
    public function glyphWithAutoMarginPositioning()
    {
        $glyph = new Container(array('width' => 100, 'height' => 100));
        $glyph->hadAutoMargins(true);

        $child = new Container(array('width' => 50, 'height' => 50));
        $glyph->add($child);
        $page = new Page();
        $page->add($glyph);

        foreach(array($glyph, $child) as $g)
        {
            $this->formatter->preFormat($g);
            if($g === $glyph)
            {
                $g->setWidth(110);
            }
            $this->formatter->postFormat($g);
        }

        $glyphBoundary = $glyph->getBoundary();
        $childBoundary = $child->getBoundary();
        $pageBoundary = $page->getBoundary();


        $this->assertEquals($pageBoundary[0]->translate(-5, 0), $glyphBoundary[0]);
        $this->assertEquals($pageBoundary[0]->translate(105, 0), $glyphBoundary[1]);
        $this->assertEquals($pageBoundary[0]->translate(105, 100), $glyphBoundary[2]);
        $this->assertEquals($pageBoundary[0]->translate(-5, 100), $glyphBoundary[3]);
    }
}