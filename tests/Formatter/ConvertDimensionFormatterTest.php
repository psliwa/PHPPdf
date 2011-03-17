<?php

use PHPPdf\Document;
use PHPPdf\Glyph\Page;
use PHPPdf\Glyph\Container;
use PHPPdf\Formatter\ConvertDimensionFormatter;

class ConvertDimensionFormatterTest extends PHPUnit_Framework_TestCase
{
    private $formatter;
    private $document;

    public function setUp()
    {
        $this->formatter = new ConvertDimensionFormatter();
        $this->document = new Document();
    }

    /**
     * @test
     */
    public function percentageConvert()
    {
        $page = new Page();
        $glyph = new Container(array('width' => 200, 'height' => 100));

        $child = new Container(array('width' => '70%', 'height' => '50%'));
        $glyph->add($child);
        $page->add($glyph);

        $glyph->setHeight(100);
        $glyph->setWidth(200);

        $this->formatter->format($child, $this->document);

        $this->assertEquals(200*0.7, $child->getWidth());
        $this->assertEquals(100*0.5, $child->getHeight());
    }

    /**
     * @test
     */
    public function autoMarginConvert()
    {
        $glyph = new Container(array('width' => 100));
        $glyph->setWidth(100);
        $glyph->setMargin(0, 'auto');

        $mockRealWidth = 200;
        $mock = $this->getMock('\PHPPdf\Glyph\Page', array('getWidth'));
        $mock->expects($this->exactly(2))
             ->method('getWidth')
             ->will($this->returnValue($mockRealWidth));

        $mock->add($glyph);

        $this->formatter->format($glyph, $this->document);

        $this->assertEquals(($mockRealWidth - $glyph->getWidth())/2, $glyph->getMarginLeft());
    }

    /**
     * @test
     */
    public function colorConvert()
    {
        $page = new Page();
        $glyph = new Container(array('color' => '#ffffff'));
        $page->add($glyph);

        $this->formatter->format($glyph, $this->document);

        $this->assertTrue($glyph->getColor() instanceof Zend_Pdf_Color);
    }

    /**
     * @test
     */
    public function fontConvert()
    {
        $fontStub = 'fontStub';

        $registryMock = $this->getMock('PHPPdf\Font\Registry', array('get'));
        $registryMock->expects($this->once())
                     ->method('get')
                     ->with($this->equalTo('verdana'))
                     ->will($this->returnValue($fontStub));

        $documentMock = $this->getMock('PHPPdf\Document', array('getFontRegistry'));
        $documentMock->expects($this->once())
                ->method('getFontRegistry')
                ->will($this->returnValue($registryMock));

        $glyphMock = $this->getMock('PHPPdf\Glyph\Container', array('setFontType', 'getFontType', 'getParent'));
        $glyphMock->expects($this->once())
                  ->method('setFontType')
                  ->with($fontStub);
        $glyphMock->expects($this->once())
                  ->method('getFontType')
                  ->will($this->returnValue('verdana'));
        $glyphMock->expects($this->any())
                  ->method('getParent')
                  ->will($this->returnValue(new Page()));


        $this->formatter->format($glyphMock, $documentMock);
    }
}