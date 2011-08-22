<?php

use PHPPdf\Document;
use PHPPdf\Glyph\Glyph;
use PHPPdf\Glyph\Container;
use PHPPdf\Formatter\StandardDimensionFormatter;

class StandardDimensionFormatterTest extends PHPUnit_Framework_TestCase
{
    private $formatter;
    private $document;

    public function setUp()
    {
        $this->formatter = new StandardDimensionFormatter();
        $this->document = new Document();
    }

    /**
     * @test
     */
    public function glyphFormatter()
    {
        $glyph = $this->getMock('PHPPdf\Glyph\Glyph', array('getWidth', 'getHeight', 'setWidth', 'setHeight'));
        $glyph->expects($this->atLeastOnce())
              ->method('getWidth')
              ->will($this->returnValue(120));
        $glyph->expects($this->atLeastOnce())
              ->method('getHeight')
              ->will($this->returnValue(140));
        $glyph->expects($this->once())
              ->method('setWidth')
              ->with($this->equalTo(120));
        $glyph->expects($this->once())
              ->method('setHeight')
              ->with($this->equalTo(140));

        $this->formatter->format($glyph, $this->document);
    }

    /**
     * @test
     */
    public function setZeroWidthGlyphsWithFloat()
    {
        $glyph = $this->getMock('PHPPdf\Glyph\Container', array('getWidth', 'setWidth', 'getFloat'));

        $glyph->expects($this->atLeastOnce())
              ->method('getWidth')
              ->will($this->returnValue(null));
        $glyph->expects($this->atLeastOnce())
              ->method('setWidth')
              ->with($this->equalTo(0));
        $glyph->expects($this->atLeastOnce())
              ->method('getFloat')
              ->will($this->returnValue('left'));

        $this->formatter->format($glyph, $this->document);
    }
    
    /**
     * @test
     * @dataProvider dimensionProvider
     */
    public function useRealWidthAndHeightToIncraseDimensionsByPaddings($realWidth, $realHeight, array $paddings)
    {
        $glyph = $this->getMockBuilder('PHPPdf\Glyph\Container')
                      ->setMethods(array('getRealWidth', 'getRealHeight', 'getWidth', 'getHeight', 'setHeight', 'setWidth', 'getPaddingLeft', 'getPaddingTop', 'getPaddingRight', 'getPaddingBottom'))
                      ->getMock();
                      
        foreach($paddings as $method => $value)
        {
            $glyph->expects($this->atLeastOnce())
                  ->method($method)
                  ->will($this->returnValue($value));
        }
        
        //width and height might be random, becouse real width and height will be used
        //to incrase dimension
        foreach(array('getWidth', 'getHeight') as $method)
        {
            $glyph->expects($this->atLeastOnce())
                  ->method($method)
                  ->will($this->returnValue(rand(1, 200)));
        }        
        
        $glyph->expects($this->atLeastOnce())
              ->method('getRealWidth')
              ->will($this->returnValue($realWidth));

        $glyph->expects($this->atLeastOnce())
              ->method('getRealHeight')
              ->will($this->returnValue($realHeight));
              
        $glyph->expects($this->once())
              ->method('setWidth')
              ->with($realWidth + $paddings['getPaddingLeft'] + $paddings['getPaddingRight']);

        $glyph->expects($this->once())
              ->method('setHeight')
              ->with($realHeight + $paddings['getPaddingTop'] + $paddings['getPaddingBottom']);

        $this->formatter->format($glyph, $this->document);
    }
    
    public function dimensionProvider()
    {
        return array(
            array(200, 300, array(
                'getPaddingLeft' => 10,
                'getPaddingTop' => 11,
                'getPaddingRight' => 12,
                'getPaddingBottom' => 13,
            )),
        );
    }
    
    /**
     * @test
     */
    public function widthCantExceedWidthOfParent()
    {
        $parentWidth = 100;
        $parentHeight = 100;
        $childWidth = 90;
        $childHeight = 90;
        
        $paddingHorizontal = 10;
        $paddingVertical = 10;
        
        $parentPaddingHorizontal = 2;
        $parentPaddingVertical = 2;
        
        $parent = new Container();
        $parent->setWidth($parentWidth);
        $parent->setHeight($parentHeight);
        $parent->setPadding($parentPaddingVertical, $parentPaddingHorizontal);
        
        $glyph = new Container();
        $glyph->setWidth($childWidth);        
        $glyph->setHeight($childHeight);    
        $glyph->setPadding($paddingVertical, $paddingHorizontal);
        
        $parent->add($glyph);
        
        $this->formatter->format($glyph, $this->document);
        
        $this->assertEquals($parent->getWidthWithoutPaddings(), $glyph->getWidth());
        $this->assertEquals($childHeight + 2*$paddingVertical, $glyph->getHeight());
    }
}