<?php

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\PdfUnitConverter;

use PHPPdf\Core\Document;
use PHPPdf\Core\Node\Page;
use PHPPdf\Core\Node\Container;
use PHPPdf\Core\Formatter\ConvertAttributesFormatter;

class ConvertAttributesFormatterTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $formatter;
    private $document;

    public function setUp()
    {
        $this->formatter = new ConvertAttributesFormatter();

        $this->document = $this->createDocumentStub();
    }

    /**
     * @test
     */
    public function percentageConvert()
    {
        $page = new Page();
        $unitConverter = new PdfUnitConverter();
        $node = new Container(array('width' => 200, 'height' => 100), $unitConverter);
        $child = new Container(array('width' => '70%', 'height' => '50%'), $unitConverter);

        $node->add($child);
        $page->add($node);

        $node->setHeight(100);
        $node->setWidth(200);

        $this->formatter->format($child, $this->document);

        $this->assertEquals(200*0.7, $child->getWidth());
        $this->assertEquals(100*0.5, $child->getHeight());
    }

    /**
     * @test
     * @dataProvider autoMarginConvertProvider
     */
    public function autoMarginConvert($nodeWidth, $parentWidth, $expectedMarginLeft, $expectedMarginRight)
    {
        $node = new Container(array('width' => $nodeWidth));
        $node->setWidth($nodeWidth);
        $node->setMargin(0, 'auto');

        $mock = $this->getMock('\PHPPdf\Core\Node\Page', array('getWidth', 'setWidth'));
        $mock->expects($this->atLeastOnce())
             ->method('getWidth')
             ->will($this->returnValue($parentWidth));
             
        if($nodeWidth > $parentWidth)
        {
            $mock->expects($this->once())
                 ->method('setWidth')
                 ->with($nodeWidth);
        }

        $mock->add($node);

        $this->formatter->format($node, $this->document);

        $this->assertEquals($expectedMarginLeft, $node->getMarginLeft());
        $this->assertEquals($expectedMarginRight, $node->getMarginRight());
    }
    
    public function autoMarginConvertProvider()
    {
        return array(
            array(100, 200, 50, 50),
            array(200, 100, 0, 0), // if child is wider than parent, margins should be set as "0" and parent width should be set as child width
        );
    }
    
    /**
     * @test
     * @dataProvider angleProvider
     */
    public function convertRotateAngleFronDegreesToRadians($angle, $expectedRadians)
    {
        $node = new Container();
        $node->setAttribute('rotate', $angle);
        
        $this->formatter->format($node, $this->document);
        
        if($angle === null)
        {
            $this->assertNull($node->getAttribute('rotate'));
        }
        else
        {
            $this->assertEquals($expectedRadians, $node->getAttribute('rotate'), 'conversion from degrees to radians failure', 0.001);
        }
    }
    
    public function angleProvider()
    {
        return array(
            array(0, 0),
            array('180deg', pi()),
            array(pi(), pi()),
            array('45deg', pi()/4),
        );
    }
    
    /**
     * @test
     */
    public function convertColor()
    {
        $color = 'color';
        $result = '#000000';
        
        $node = new Container();
        $node->setAttribute('color', $color);
        
        $document = $this->getMockBuilder('PHPPdf\Core\Document')
                         ->setMethods(array('getColorFromPalette'))
                         ->disableOriginalConstructor()
                         ->getMock();
                         
        $document->expects($this->once())
                 ->method('getColorFromPalette')
                 ->with($color)
                 ->will($this->returnValue($result));
                 
        $this->formatter->format($node, $document);
        
        $this->assertEquals($result, $node->getAttribute('color'));
    }
}