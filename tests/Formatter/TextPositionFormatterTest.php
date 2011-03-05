<?php

use PHPPdf\Document;
use PHPPdf\Util\Point;
use PHPPdf\Glyph\AbstractGlyph;
use PHPPdf\Glyph\Container;
use PHPPdf\Glyph\Page;
use PHPPdf\Formatter\TextPositionFormatter;

class TextPositionFormatterTest extends PHPUnit_Framework_TestCase
{
    private $formatter;

    public function setUp()
    {
        $this->formatter = new TextPositionFormatter();
        $this->formatter->setDocument(new Document());
    }

    /**
     * @test
     */
    public function inlineTextWithoutSiblingPosition()
    {
        $parentMock = $this->getMock('\PHPPdf\Glyph\AbstractGlyph', array('getStartDrawingPoint'));
        $parentMock->expects($this->once())
                   ->method('getStartDrawingPoint')
                   ->will($this->returnValue(array(0, 700)));

        $mock = $this->getGlyphMock('\PHPPdf\Glyph\Text');
        $mock->expects($this->exactly(2))
             ->method('getDisplay')
             ->will($this->returnValue(PHPPdf\Glyph\AbstractGlyph::DISPLAY_INLINE));
        $mock->expects($this->once())
             ->method('getLineHeight')
             ->will($this->returnValue(14));
        $mock->expects($this->once())
             ->method('getLineSizes')
             ->will($this->returnValue(array(100, 50)));
        $mock->expects($this->once())
             ->method('getParent')
             ->will($this->returnValue($parentMock));


        $this->formatter->preFormat($mock);
        $this->formatter->postFormat($mock);
    }
    
    private function getGlyphMock($class)
    {
        $mock = $this->getMock($class, array(
            'getPage',
            'setStartDrawingPoint',
            'getParent',
            'getPreviousSibling',
            'getWidth',
            'getHeight',
            'getDisplay',
            'getLineHeight',
            'getLineSizes',
            'getStartDrawingPoint',
            'getBoundary',
        ));

        $boundaryMock = $this->getMock('\PHPPdf\Util\Boundary', array(
            'getFirstPoint',
        ));
        $boundaryMock->expects($this->atLeastOnce())
                     ->method('getFirstPoint')
                     ->will($this->returnValue(Point::getInstance(0, 700)));
        
        $page = new Page();

        $mock->expects($this->any())
             ->method('getStartDrawingPoint')
             ->will($this->returnValue(array(0, $page->getHeight())));
        $mock->expects($this->atLeastOnce())
             ->method('getBoundary')
             ->will($this->returnValue($boundaryMock));


        return $mock;
    }
}