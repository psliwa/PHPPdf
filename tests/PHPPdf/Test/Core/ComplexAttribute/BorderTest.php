<?php

namespace PHPPdf\Test\Core\ComplexAttribute;

use PHPPdf\Core\Node\Container;

use PHPPdf\Core\PdfUnitConverter;
use PHPPdf\Core\Document;
use PHPPdf\ObjectMother\NodeObjectMother;
use PHPPdf\Core\ComplexAttribute\Border;
use PHPPdf\Core\Boundary;
use PHPPdf\Core\Node\Page;
use PHPPdf\Core\Point;
use PHPPdf\Core\Engine\GraphicsContext;

class BorderTest extends ComplexAttributeTest
{
    private $border;
    private $objectMother;

    public function init()
    {
        $this->objectMother = new NodeObjectMother($this);
    }

    public function setUp()
    {
        $this->border = new Border();
        $this->document = new Document($this->getMock('PHPPdf\Core\Engine\Engine'));
        $this->document->setUnitConverter(new PdfUnitConverter());
    }

    /**
     * @test
     */
    public function genericEnhance()
    {
        $x = 0;
        $y = 100;
        $width = 100;
        $height = 50;

        $gcMock = $this->getMockBuilder('PHPPdf\Core\Engine\GraphicsContext')
                       ->getMock();
        $gcMock->expects($this->once())
                 ->method('drawPolygon')
                 ->with(array(-0.5, 100, 100, 0, 0), array(100, 100, 50, 50, 100.5), GraphicsContext::SHAPE_DRAW_STROKE);

        $nodeMock = $this->objectMother->getNodeMock($x, $y, $width, $height, $gcMock);

        $this->border->enhance($nodeMock, $this->document);
    }

    /**
     * @test
     * @dataProvider getTypes
     */
    public function settingBorderTypes($typePassed, $typeExcepted)
    {
        $border = new Border(null, $typePassed);
        $this->assertEquals($typeExcepted, $border->getType());
    }

    public function getTypes()
    {
        return array(
            array('left+right', Border::TYPE_LEFT | Border::TYPE_RIGHT),
            array(Border::TYPE_LEFT | Border::TYPE_RIGHT, Border::TYPE_LEFT | Border::TYPE_RIGHT),
        );
    }

    /**
     * @test
     */
    public function defaultBorderType()
    {
        $this->assertEquals(Border::TYPE_ALL, $this->border->getType());
    }

    /**
     * @test
     */
    public function drawingPartialBorder()
    {
        $gcMock = $this->getMockBuilder('PHPPdf\Core\Engine\GraphicsContext')
			   ->getMock();

        //at(0) and at(1) for setLineDashingPattern and setLineWidth
        $gcMock->expects($this->at(2))
               ->method('drawLine')
               ->with(-0.5, 100, 50.5, 100);
        
        $gcMock->expects($this->at(3))
               ->method('drawLine')
               ->with(50.5, 50, -0.5, 50);

        $nodeMock = $this->objectMother->getNodeMock(0, 100, 50, 50, $gcMock);

        $border = new Border(null, Border::TYPE_TOP | Border::TYPE_BOTTOM);

        $border->enhance($nodeMock, $this->document);
    }

    /**
     * @test
     */
    public function borderWithNotStandardSize()
    {
        $document = $this->getMockBuilder('PHPPdf\Core\Document')
                         ->setMethods(array('convertUnit'))
                         ->disableOriginalConstructor()
                         ->getMock();
        
        $actualSize = '12px';
        $expectedSize = 2;
        
        $document->expects($this->at(0))
                 ->method('convertUnit')
                 ->with($actualSize)
                 ->will($this->returnValue($expectedSize));
                 
        //position conversion
        $document->expects($this->at(1))
                 ->method('convertUnit')
                 ->with(0)
                 ->will($this->returnValue(0));

        $gcMock = $this->getMockBuilder('PHPPdf\Core\Engine\GraphicsContext')
        			   ->getMock();

        $gcMock->expects($this->once())
               ->method('setLineWidth')
               ->with($expectedSize);
        
        $gcMock->expects($this->once())
               ->method('drawLine')
               ->with(0, 2, 0, 11);

        $nodeMock = $this->objectMother->getNodeMock(0, 10, 5, 7, $gcMock);

        $border = new Border(null, Border::TYPE_LEFT, $actualSize);

        $border->enhance($nodeMock, $document);
    }

    /**
     * @test
     */
    public function fullRadiusBorder()
    {
        $radius = 50;

        $gcMock = $this->getMockBuilder('PHPPdf\Core\Engine\GraphicsContext')
                       ->getMock();
        $gcMock->expects($this->once())
               ->method('drawRoundedRectangle')
               ->with(0, 70, 50, 100, $radius, GraphicsContext::SHAPE_DRAW_STROKE);

        $nodeMock = $this->objectMother->getNodeMock(0, 100, 50, 30, $gcMock);

        $border = new Border(null, Border::TYPE_ALL, 1, $radius);

        $border->enhance($nodeMock, $this->document);
    }

    /**
     * @test
     */
    public function settingRadiusInStringStyle()
    {
        $border = new Border(null, Border::TYPE_ALL, 1, '5 5');

        $this->assertEquals(array(5, 5, 5, 5), $border->getRadius());
    }

    /**
     * @test
     */
    public function settingCustomizedDashingPatternInStringStyle()
    {
        $border = new Border(null, Border::TYPE_ALL, 1, null, '1 2 3');

        $this->assertEquals(array(1, 2, 3), $border->getStyle());
    }

    /**
     * @test
     * @dataProvider borderStyleProvider
     */
    public function borderStyle($style)
    {
        $gcMock = $this->getMockBuilder('PHPPdf\Core\Engine\GraphicsContext')
        			   ->getMock();

        $gcMock->expects($this->at(0))
               ->method('setLineDashingPattern')
               ->with($style);

        //at(1) for setLineWidth

        $gcMock->expects($this->at(2))
               ->method('drawLine')
               ->with(0, 69.5, 0, 100.5);

        $nodeMock = $this->objectMother->getNodeMock(0, 100, 50, 30, $gcMock);

        $border = new Border(null, Border::TYPE_LEFT, 1, null, $style);

        $border->enhance($nodeMock, $this->document);
    }

    public function borderStyleProvider()
    {
        return array(
            array(Border::STYLE_SOLID),
            array(Border::STYLE_DOTTED),
        );
    }

    /**
     * @test
     * @dataProvider borderStyleByStringProvider
     */
    public function settingBorderStyleByString($style, $excepted)
    {
        $border = new Border(null, Border::TYPE_LEFT, 1, null, $style);
        $this->assertEquals($excepted, $border->getStyle());
    }

    public function borderStyleByStringProvider()
    {
        return array(
            array('solid', Border::STYLE_SOLID),
            array('dotted', Border::STYLE_DOTTED),
        );
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function throwExceptionIfBorderStyleIsInvalid()
    {
        new Border(null, Border::TYPE_LEFT, 1, null, 'invalid_style');
    }

    /**
     * @test
     */
    public function positionCorrectionInFullBorder()
    {
        $x = 0;
        $y = 100;
        $width = 50;
        $height = 30;
        $actualPosition = '12px';
        $expectedPosition = 2;
        $size = 1;
        
        $document = $this->getMockBuilder('PHPPdf\Core\Document')
                         ->setMethods(array('convertUnit'))
                         ->disableOriginalConstructor()
                         ->getMock();
                         
        //size conversion
        $document->expects($this->at(0))
                 ->method('convertUnit')
                 ->with($size)
                 ->will($this->returnValue($size));
        $document->expects($this->at(1))
                 ->method('convertUnit')
                 ->with($actualPosition)
                 ->will($this->returnValue($expectedPosition));
        
        $border = new Border(null, Border::TYPE_ALL, $size, null, Border::STYLE_SOLID, $actualPosition);

        $gcMock = $this->getMockBuilder('PHPPdf\Core\Engine\GraphicsContext')
                       ->getMock();

        $nodeMock = $this->objectMother->getNodeMock($x, $y, $width, $height, $gcMock);
        $halfSize = $size/2;

        $gcMock->expects($this->once())
               ->method('drawPolygon')
               ->with(array($x-$halfSize-$expectedPosition, $x+$width+$expectedPosition, $x+$width+$expectedPosition, $x-$expectedPosition, $x-$expectedPosition),
                      array($y+$expectedPosition, $y+$expectedPosition, $y-$height-$expectedPosition, $y-$height-$expectedPosition, $y+$halfSize+$expectedPosition));

        $border->enhance($nodeMock, $document);
    }

    /**
     * @test
     */
    public function positionCorrectionInPartialBorder()
    {
        $x = 0;
        $y = 100;
        $width = 50;
        $height = 30;

        $type = Border::TYPE_BOTTOM;
        $actualPosition = '12px';
        $expectedPosition = 2;
        $size = 1;
        $halfSize = $size/2;
        
        $document = $this->getMockBuilder('PHPPdf\Core\Document')
                         ->setMethods(array('convertUnit'))
                         ->disableOriginalConstructor()
                         ->getMock();
                         
        //size conversion
        $document->expects($this->at(0))
                 ->method('convertUnit')
                 ->with($size)
                 ->will($this->returnValue($size));
        $document->expects($this->at(1))
                 ->method('convertUnit')
                 ->with($actualPosition)
                 ->will($this->returnValue($expectedPosition));

        $border = new Border(null, $type, $size, null, Border::STYLE_SOLID, $actualPosition);

        $gcMock = $this->getMockBuilder('PHPPdf\Core\Engine\GraphicsContext')
        			   ->getMock();

        $nodeMock = $this->objectMother->getNodeMock($x, $y, $width, $height, $gcMock);

        $gcMock->expects($this->once())
               ->method('drawLine')
               ->with($x+$width+$expectedPosition+$halfSize, $y-$height-$expectedPosition, $x-$expectedPosition-$halfSize, $y-$height-$expectedPosition);

        $border->enhance($nodeMock, $document);
    }
    
    /**
     * @test
     * @dataProvider typeProvider
     */
    public function borderWithNoneAsTypeIsEmpty($type, $expectedEmpty)
    {
        $border = new Border(null, $type);
        
        $this->assertEquals($expectedEmpty, $border->isEmpty());
    }
    
    public function typeProvider()
    {
        return array(
            array(Border::TYPE_NONE, true),
            array(Border::TYPE_ALL, false),
            array(Border::TYPE_LEFT, false),
        );
    }
    
    /**
     * @test
     */
    public function convertColorViaDocumentColorPalette()
    {
        $color = 'color';
        $expectedColor = '#123123';
        
        $border = new Border($color);
        
        $gcMock = $this->getMock('PHPPdf\Core\Engine\GraphicsContext');
        
        $document = $this->getMockBuilder('PHPPdf\Core\Document')
                         ->setMethods(array('getColorFromPalette'))
                         ->disableOriginalConstructor()
                         ->getMock();
                         
        $document->expects($this->once())
                 ->method('getColorFromPalette')
                 ->with($color)
                 ->will($this->returnValue($expectedColor));
        
        foreach(array('setLineColor', 'setFillColor') as $method)
        {
            $gcMock->expects($this->once())
                   ->method($method)
                   ->with($expectedColor);
        }
        
        $nodeMock = $this->objectMother->getNodeMock(0, 0, 100, 100, $gcMock);
        
        $border->enhance($nodeMock, $document);
    }
    
    /**
     * @test
     */
    public function drawCircleBorder()
    {
        $color = '#ffffff';
        $radius = 100;
        $centerPoint = Point::getInstance(100, 100);
        $background = new Border('#ffffff');
        
        $this->assertDrawCircle($background, $color, $radius, $centerPoint, GraphicsContext::SHAPE_DRAW_STROKE);       
    }
}