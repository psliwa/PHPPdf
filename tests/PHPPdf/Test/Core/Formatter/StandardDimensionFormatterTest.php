<?php

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Document;
use PHPPdf\Core\Node\Node;
use PHPPdf\Core\Node\Container;
use PHPPdf\Core\Formatter\StandardDimensionFormatter;

class StandardDimensionFormatterTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $formatter;
    private $document;

    public function setUp()
    {
        $this->formatter = new StandardDimensionFormatter();
        $this->document = $this->createDocumentStub();
    }

    /**
     * @test
     */
    public function nodeFormatter()
    {
        $node = $this->getMock('PHPPdf\Core\Node\Node', array('getWidth', 'getHeight', 'setWidth', 'setHeight'));
        $node->expects($this->atLeastOnce())
              ->method('getWidth')
              ->will($this->returnValue(120));
        $node->expects($this->atLeastOnce())
              ->method('getHeight')
              ->will($this->returnValue(140));
        $node->expects($this->once())
              ->method('setWidth')
              ->with($this->equalTo(120));
        $node->expects($this->once())
              ->method('setHeight')
              ->with($this->equalTo(140));

        $this->formatter->format($node, $this->document);
    }

    /**
     * @test
     */
    public function setZeroWidthNodesWithFloat()
    {
        $node = $this->getMock('PHPPdf\Core\Node\Container', array('getWidth', 'setWidth', 'getFloat'));

        $node->expects($this->atLeastOnce())
              ->method('getWidth')
              ->will($this->returnValue(null));
        $node->expects($this->atLeastOnce())
              ->method('setWidth')
              ->with($this->equalTo(0));
        $node->expects($this->atLeastOnce())
              ->method('getFloat')
              ->will($this->returnValue('left'));

        $this->formatter->format($node, $this->document);
    }
    
    /**
     * @test
     * @dataProvider dimensionProvider
     */
    public function useRealWidthAndHeightToIncraseDimensionsByPaddings($realWidth, $realHeight, array $paddings)
    {
        $node = $this->getMockBuilder('PHPPdf\Core\Node\Container')
                      ->setMethods(array('getRealWidth', 'getRealHeight', 'getWidth', 'getHeight', 'setHeight', 'setWidth', 'getPaddingLeft', 'getPaddingTop', 'getPaddingRight', 'getPaddingBottom'))
                      ->getMock();
                      
        foreach($paddings as $method => $value)
        {
            $node->expects($this->atLeastOnce())
                  ->method($method)
                  ->will($this->returnValue($value));
        }
        
        //width and height might be random, becouse real width and height will be used
        //to incrase dimension
        foreach(array('getWidth', 'getHeight') as $method)
        {
            $node->expects($this->atLeastOnce())
                  ->method($method)
                  ->will($this->returnValue(rand(1, 200)));
        }        
        
        $node->expects($this->atLeastOnce())
              ->method('getRealWidth')
              ->will($this->returnValue($realWidth));

        $node->expects($this->atLeastOnce())
              ->method('getRealHeight')
              ->will($this->returnValue($realHeight));
              
        $node->expects($this->once())
              ->method('setWidth')
              ->with($realWidth + $paddings['getPaddingLeft'] + $paddings['getPaddingRight']);

        $node->expects($this->once())
              ->method('setHeight')
              ->with($realHeight + $paddings['getPaddingTop'] + $paddings['getPaddingBottom']);

        $this->formatter->format($node, $this->document);
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
        
        $node = new Container();
        $node->setWidth($childWidth);        
        $node->setHeight($childHeight);    
        $node->setPadding($paddingVertical, $paddingHorizontal);
        
        $parent->add($node);
        
        $this->formatter->format($node, $this->document);
        
        $this->assertEquals($parent->getWidthWithoutPaddings(), $node->getWidth());
        $this->assertEquals($childHeight + 2*$paddingVertical, $node->getHeight());
    }
}