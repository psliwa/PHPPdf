<?php

namespace PHPPdf\Test\Core\ComplexAttribute;

use PHPPdf\Core\Node\Circle;
use PHPPdf\Core\Engine\GraphicsContext;
use PHPPdf\ObjectMother\NodeObjectMother;
use PHPPdf\Core\Document;
use PHPPdf\Core\ComplexAttribute\Background,
    PHPPdf\Core\Node\Page,
    PHPPdf\Core\Point;

class BackgroundTest extends ComplexAttributeTest
{
    const IMAGE_WIDTH = 30;
    const IMAGE_HEIGHT = 30;
    
    private $objectMother;

    public function init()
    {
        $this->objectMother = new NodeObjectMother($this);
    }

    public function setUp()
    {
        $this->document = $this->getMockBuilder('PHPPdf\Core\Document')
                               ->setMethods(array('convertUnit'))
                               ->disableOriginalConstructor()
                               ->getMock();
    }

    /**
     * @test
     */
    public function backgroundWithoutRepeat()
    {
        $imageWidth = 100;
        $imageHeight = 120;
        
        $imagePath = 'image/path';
        $background = new Background(null, $imagePath);
        
        $image = $this->createImageMock($imageWidth, $imageHeight);        
        $document = $this->createDocumentMock($imagePath, $image);

        $x = 0;
        $y = 200;
        $width = $height = 100;
        
        $gcMock = $this->getMockBuilder('PHPPdf\Core\Engine\GraphicsContext')
                       ->getMock();

        $nodeMock = $this->getNodeMock($x, $y, $width, $height, $gcMock);

        $gcMock->expects($this->at(0))
               ->method('saveGS');

        $gcMock->expects($this->at(1))
               ->method('clipRectangle')
               ->with($x, $y, $x+$width, $y-$height);

        $gcMock->expects($this->at(2))
               ->method('drawImage')
               ->with($image, $x, $y-$imageHeight, $x+$imageWidth, $y);

        $gcMock->expects($this->at(3))
               ->method('restoreGS');

        $background->enhance($nodeMock, $document);
    }
    
    private function createImageMock($width, $height)
    {
        $image = $this->getMockBuilder('PHPPdf\Core\Engine\Image')
                      ->setMethods(array('getOriginalHeight', 'getOriginalWidth'))
                      ->disableOriginalConstructor()
                      ->getMock();
                      
        $image->expects($this->atLeastOnce())
              ->method('getOriginalHeight')
              ->will($this->returnValue($height));
        $image->expects($this->atLeastOnce())
              ->method('getOriginalWidth')
              ->will($this->returnValue($width));
              
        return $image;
    }
    
    private function createDocumentMock($imagePath, $image, $mockUnitConverterInterface = true)
    {
        $methods = array('createImage');
        
        if($mockUnitConverterInterface)
        {
            $methods = array_merge($methods, array('convertUnit', 'convertPercentageValue'));
        }
        
        $document = $this->getMockBuilder('PHPPdf\Core\Document')
                         ->setMethods($methods)
                         ->disableOriginalConstructor()
                         ->getMock();
        $document->expects($this->once())
                 ->method('createImage')
                 ->with($imagePath)
                 ->will($this->returnValue($image));
                 
        return $document;
    }

    /**
     * @test
     * @dataProvider kindOfBackgroundsProvider
     */
    public function backgroundWithRepeat($repeat)
    {
        $x = 0;
        $y = 200;
        $width = $height = 100;
        
        $imageWidth = 100;
        $imageHeight = 120;
        $imagePath = 'image/path';

        $image = $this->createImageMock($imageWidth, $imageHeight);        
        $document = $this->createDocumentMock($imagePath, $image);
        
        $background = new Background(null, $imagePath, $repeat);

        $x = 1;
        if($repeat & Background::REPEAT_X)
        {
            $x = ceil($width / $imageWidth);
        }

        $y = 1;
        if($repeat & Background::REPEAT_Y)
        {
            $y = ceil($height / $imageHeight);
        }

        $count = (int) ($x*$y);

        $gcMock = $this->getMockBuilder('PHPPdf\Core\Engine\GraphicsContext')
        			   ->getMock();

        $nodeMock = $this->getNodeMock($x, $y, $width, $height, $gcMock);

        $gcMock->expects($this->once())
               ->method('saveGS');

        $gcMock->expects($this->once())
               ->method('clipRectangle')
               ->with($x, $y, $x+$width, $y-$height);

        $gcMock->expects($this->exactly($count))
               ->method('drawImage');

        $gcMock->expects($this->once())
               ->method('restoreGS');


        $background->enhance($nodeMock, $document);
    }

    public function kindOfBackgroundsProvider()
    {
        return array(
            array(Background::REPEAT_X),
            array(Background::REPEAT_Y),
            array(Background::REPEAT_ALL),
        );
    }

    private function getNodeMock($x, $y, $width, $height, $gcMock)
    {
        $boundaryMock = $this->getBoundaryStub($x, $y, $width, $height);

        $nodeMock = $this->getMock('PHPPdf\Core\Node\Node', array('getBoundary', 'getWidth', 'getHeight', 'getGraphicsContext'));
        $nodeMock->expects($this->atLeastOnce())
                  ->method('getBoundary')
                  ->will($this->returnValue($boundaryMock));
        $nodeMock->expects($this->any())
                  ->method('getWidth')
                  ->will($this->returnValue($width));

        $nodeMock->expects($this->any())
                  ->method('getHeight')
                  ->will($this->returnValue($height));

        $nodeMock->expects($this->atLeastOnce())
                  ->method('getGraphicsContext')
                  ->will($this->returnValue($gcMock));

        return $nodeMock;
    }

    private function getBoundaryStub($x, $y, $width, $height)
    {
        $boundaryMock = new \PHPPdf\Core\Boundary();

        $points = array(
            Point::getInstance($x, $y),
            Point::getInstance($x+$width, $y),
            Point::getInstance($x+$width, $y - $height),
            Point::getInstance($x, $y - $height),
        );

        foreach($points as $point)
        {
            $boundaryMock->setNext($point);
        }
        $boundaryMock->close();

        return $boundaryMock;
    }

    /**
     * @test
     */
    public function radiusColorBorder()
    {
        $radius = 50;

        $gcMock = $this->getMockBuilder('PHPPdf\Core\Engine\GraphicsContext')
                       ->getMock();
        $gcMock->expects($this->once())
               ->method('drawRoundedRectangle')
               ->with(0, 70, 50, 100, $radius, GraphicsContext::SHAPE_DRAW_FILL_AND_STROKE);

        $this->addStandardExpectationsToGraphicContext($gcMock);

        $nodeMock = $this->getNodeMock(0, 100, 50, 30, $gcMock);

        $border = new Background('black', null, Background::REPEAT_ALL, $radius);

        $border->enhance($nodeMock, $this->document);
    }
    
    private function addStandardExpectationsToGraphicContext($gcMock)
    {
        $gcMock->expects($this->once())
               ->method('saveGS');
        $gcMock->expects($this->once())
               ->method('restoreGS');
        $gcMock->expects($this->once())
               ->method('setLineColor');
        $gcMock->expects($this->once())
               ->method('setFillColor');
    }
    
    /**
     * @test
     * @dataProvider repeatProvider
     */
    public function convertRepeatAsStringToConstat($string, $expected)
    {
        $complexAttribute = new Background(null, null, $string);
        
        $this->assertEquals($expected, $complexAttribute->getRepeat());
    }
    
    public function repeatProvider()
    {
        return array(
            array('none', Background::REPEAT_NONE),
            array('x', Background::REPEAT_X),
            array('y', Background::REPEAT_Y),
            array('all', Background::REPEAT_ALL),
        );
    }
    
    /**
     * @test
     */
    public function useRealBoundaryWhenRealDimensionParameterIsSetted()
    {
        $complexAttribute = new Background('black', null, Background::REPEAT_ALL, null, true);
        
        $node = $this->getMockBuilder('PHPPdf\Core\Node\Container')
                      ->setMethods(array('getRealBoundary', 'getBoundary', 'getGraphicsContext'))
                      ->getMock();

        $height = 100;
        $width = 100;        
        $boundary = $this->getBoundaryStub(0, 100, $width, $height);
                      
        $node->expects($this->atLeastOnce())
              ->method('getRealBoundary')
              ->will($this->returnValue($boundary));

        $node->expects($this->never())
              ->method('getBoundary');
                     
        $gc = $this->getMockBuilder('PHPPdf\Core\Engine\GraphicsContext')
                   ->getMock();
                   
        $expectedXCoords = array(
            $boundary[0]->getX(),
            $boundary[1]->getX(),
            $boundary[1]->getX(),
            $boundary[0]->getX(),
            $boundary[0]->getX(),
        );
        $expectedYCoords = array(
            $boundary[0]->getY(),
            $boundary[0]->getY(),
            $boundary[2]->getY(),
            $boundary[2]->getY(),
            $boundary[0]->getY(),
        );

        $gc->expects($this->once())
           ->method('drawPolygon')
           ->with($expectedXCoords, $expectedYCoords, $this->anything());
                     
        $node->expects($this->atLeastOnce())
              ->method('getGraphicsContext')
              ->will($this->returnValue($gc));
             
        $complexAttribute->enhance($node, $this->document);
    }
    
    /**
     * @test
     * @dataProvider booleanProvider
     */
    public function convertStringBooleanValuesToBooleanTypeForUseRealDimensionParameter($value, $expected)
    {
        $complexAttribute = new Background('black', null, Background::REPEAT_ALL, null, $value);
        
        $this->assertTrue($expected === $this->readAttribute($complexAttribute, 'useRealDimension'));
    }
    
    public function booleanProvider()
    {
        return array(
            array('1', true),
            array('0', false),
            array('true', true),
            array('false', false),
            array('no', false),
            array('yes', true),
        );
    }

    /**
     * @test
     * @dataProvider imageDimensionProvider
     */
    public function useBackgrounImageDimension($percentWidth, $expectedWidth, $percentHeight, $expectedHeight, $expectedHorizontalTranslation, $expectedVertiacalTranslation, $nodeWidth = 100, $nodeHeight = 100)
    {
        $imagePath = 'image/path';

        $image = $this->createImageMock(self::IMAGE_WIDTH, self::IMAGE_HEIGHT);        
        $document = $this->createDocumentMock($imagePath, $image);
               
        $x = 0;
        $y = $nodeHeight;

        $document->expects($this->at(1))
                 ->method('convertUnit')
                 ->with($percentWidth)
                 ->will($this->returnValue($percentWidth));
        $document->expects($this->at(2))
                 ->method('convertUnit')
                 ->with($percentHeight)
                 ->will($this->returnValue($percentHeight));
        $document->expects($this->at(3))
                 ->method('convertPercentageValue')
                 ->with($percentWidth, $nodeWidth)
                 ->will($this->returnValue($expectedWidth));
        $document->expects($this->at(4))
                 ->method('convertPercentageValue')
                 ->with($percentHeight, $nodeHeight)
                 ->will($this->returnValue($expectedHeight));

        $complexAttribute = new Background(null, $imagePath, Background::REPEAT_NONE, null, false, $percentWidth, $percentHeight);
        
        $gcMock = $this->getMockBuilder('PHPPdf\Core\Engine\GraphicsContext')
        			   ->getMock();

        $nodeMock = $this->getNodeMock($x, $y, $nodeWidth, $nodeHeight, $gcMock);
        
        $gcMock->expects($this->once())
               ->method('drawImage')
               ->with($image, $x, $y-$expectedVertiacalTranslation, $x+$expectedHorizontalTranslation, $y);
               
        $complexAttribute->enhance($nodeMock, $document);
    }
    
    public function imageDimensionProvider()
    {
        return array(
            array(self::IMAGE_WIDTH / 2, self::IMAGE_WIDTH / 2, null, null, self::IMAGE_WIDTH / 2, self::IMAGE_HEIGHT / 2),
            array(null, null, self::IMAGE_HEIGHT / 2, self::IMAGE_HEIGHT / 2, self::IMAGE_WIDTH / 2, self::IMAGE_HEIGHT / 2),
            array('30%', 60, null, null, 60, 60, 200, 200),
            array(null, null, '40%', 80, 80, 80, 200, 200),
        );
    }
    
    /**
     * @test
     * @dataProvider positionProvider
     */
    public function drawImageAsBackgroundInProperPosition($nodeXCoord, $positionX, $positionY, $expectedPositionX, $expectedPositionY)
    {
        $imagePath = 'image/path';

        $image = $this->createImageMock(self::IMAGE_WIDTH, self::IMAGE_HEIGHT);        
        $document = $this->createDocumentMock($imagePath, $image, false);
        
        $complexAttribute = new Background(null, $imagePath, Background::REPEAT_NONE, null, false, null, null, $positionX, $positionY);
        
        $gcMock = $this->getMockBuilder('PHPPdf\Core\Engine\GraphicsContext')
        			   ->getMock();

        $y = self::IMAGE_HEIGHT*2;
        $nodeWidth = 100;
        $nodeHeight = $y;
        			   
        $nodeMock = $this->getNodeMock($nodeXCoord, $y, $nodeWidth, $nodeHeight, $gcMock);
        
        $gcMock->expects($this->once())
               ->method('drawImage')
               ->with($image, $expectedPositionX, $expectedPositionY - self::IMAGE_HEIGHT, $expectedPositionX+self::IMAGE_WIDTH, $expectedPositionY);
               
        $complexAttribute->enhance($nodeMock, $document);
    }
    
    public function positionProvider()
    {
        return array(
            array(10, Background::POSITION_LEFT, Background::POSITION_TOP, 10, 2*self::IMAGE_HEIGHT),
            array(20, Background::POSITION_RIGHT, Background::POSITION_TOP, 20 + 100 - self::IMAGE_WIDTH, 2*self::IMAGE_HEIGHT),
            array(10, Background::POSITION_CENTER, Background::POSITION_TOP, 10 + 50 - self::IMAGE_WIDTH/2, 2*self::IMAGE_HEIGHT),
            array(15, Background::POSITION_LEFT, Background::POSITION_BOTTOM, 15, self::IMAGE_HEIGHT),
            array(13, Background::POSITION_LEFT, Background::POSITION_CENTER, 13, 2*3/4*self::IMAGE_HEIGHT),
            array(12, 40, 50, 12 + 40, self::IMAGE_HEIGHT*2 - 50),
            array(12, '40px', '50%', 12 + 40, self::IMAGE_HEIGHT*2 - 50),
        );
    }
    
    /**
     * @test
     * @dataProvider invalidPositionProvider
     * @expectedException PHPPdf\Exception\InvalidArgumentException
     */
    public function throwExceptionOnInvalidBackgroundPosition($positionX, $positionY)
    {
        new Background(null, 'path', Background::REPEAT_NONE, null, false, null, null, $positionX, $positionY);
    }
    
    public function invalidPositionProvider()
    {
        return array(
            array('a10', 10),
            array(10, 'a10'),
        );
    }
    
    /**
     * @test
     */
    public function drawCircleBackground()
    {
        $color = '#ffffff';
        $radius = 100;
        $centerPoint = Point::getInstance(100, 100);
        $background = new Background('#ffffff');
        
        $this->assertDrawCircle($background, $color, $radius, $centerPoint, GraphicsContext::SHAPE_DRAW_FILL);       
    }
}