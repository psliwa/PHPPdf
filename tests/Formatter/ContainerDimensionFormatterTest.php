<?php

use PHPPdf\Document;
use PHPPdf\Util\Boundary;
use PHPPdf\Glyph\Glyph;
use PHPPdf\Glyph\Container;
use PHPPdf\Formatter\ContainerDimensionFormatter;

class ContainerDimensionFormatterTest extends PHPUnit_Framework_TestCase
{
    private $formatter;

    public function setUp()
    {
        $this->formatter = new ContainerDimensionFormatter();
    }

    /**
     * @test
     */
    public function glyphFormatter()
    {
        $composeGlyph = new Container();
        $composeGlyph->setWidth(140);
        $children = array();
        $children[] = $this->getGlyph(0, 500, 100, 200, false);
        $children[] = $this->getGlyph(0, 300, 200, 200, false);

        foreach($children as $child)
        {
            $composeGlyph->add($child);
        }

        $this->formatter->format($composeGlyph, new Document());

        $height = 0;
        foreach($children as $child)
        {
            $height += $child->getHeight();
        }

        $this->assertEquals($height, $composeGlyph->getHeight());
        $this->assertEquals(200, $composeGlyph->getWidth());
    }

    private function getGlyph($startX, $startY, $width, $height, $sizeAssert = true)
    {
        $methods = array('getHeight', 'getBoundary');
        if($sizeAssert)
        {
            $methods[] = 'getWidth';
        }

        $mock = $this->getMock('PHPPdf\Glyph\Glyph', $methods);

        if($sizeAssert)
        {
            $mock->expects($this->atLeastOnce())
                 ->method('getWidth')
                 ->will($this->returnValue($width));
        }
        $mock->expects($sizeAssert ? $this->atLeastOnce() : $this->any())
             ->method('getHeight')
             ->will($this->returnValue($height));

        $boundary = new Boundary();
        $boundary->setNext($startX, $startY)
                 ->setNext($startX+$width, $startY)
                 ->setNext($startX+$width, $startY-$height)
                 ->setNext($startX, $startY-$height)
                 ->close();

        $mock->expects($this->once())
             ->method('getBoundary')
             ->will($this->returnValue($boundary));

        return $mock;
    }
}