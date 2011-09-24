<?php

namespace PHPPdf\Test\Enhancement;

use PHPPdf\Engine\GraphicsContext;
use PHPPdf\ObjectMother\NodeObjectMother;
use PHPPdf\Document;
use PHPPdf\Enhancement\Background,
    PHPPdf\Node\Page,
    PHPPdf\Util\Point;

class BackgroundTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    const IMAGE_WIDTH = 30;
    const IMAGE_HEIGHT = 30;
    
    private $imagePath;
    private $objectMother;
    private $document;

    public function init()
    {
        $this->objectMother = new NodeObjectMother($this);
    }

    public function setUp()
    {
        $this->imagePath = TEST_RESOURCES_DIR.'/domek-min.jpg';
        $this->document = $this->getMockBuilder('PHPPdf\Document')
                               ->setMethods(array('convertUnit'))
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
        
        $gcMock = $this->getMockBuilder('PHPPdf\Engine\GraphicsContext')
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
        $image = $this->getMockBuilder('PHPPdf\Engine\Image')
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
    
    private function createDocumentMock($imagePath, $image)
    {
        $document = $this->getMockBuilder('PHPPdf\Document')
                         ->setMethods(array('createImage', 'convertUnit'))
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

        $gcMock = $this->getMockBuilder('PHPPdf\Engine\GraphicsContext')
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

        $nodeMock = $this->getMock('PHPPdf\Node\Node', array('getBoundary', 'getWidth', 'getHeight', 'getGraphicsContext'));
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
        $boundaryMock = new \PHPPdf\Util\Boundary();

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

        $gcMock = $this->getMockBuilder('PHPPdf\Engine\GraphicsContext')
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
        $enhancement = new Background(null, null, $string);
        
        $this->assertEquals($expected, $enhancement->getRepeat());
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
        $enhancement = new Background('black', null, Background::REPEAT_ALL, null, true);
        
        $node = $this->getMockBuilder('PHPPdf\Node\Container')
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
                     
        $gc = $this->getMockBuilder('PHPPdf\Engine\GraphicsContext')
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
             
        $enhancement->enhance($node, $this->document);
    }
    
    /**
     * @test
     * @dataProvider booleanProvider
     */
    public function convertStringBooleanValuesToBooleanTypeForUseRealDimensionParameter($value, $expected)
    {
        $enhancement = new Background('black', null, Background::REPEAT_ALL, null, $value);
        
        $this->assertTrue($expected === $this->readAttribute($enhancement, 'useRealDimension'));
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
    public function useBackgrounImageDimension($imageWidth, $imageHeight, $expectedHorizontalTranslation, $expectedVertiacalTranslation, $nodeWidth = 100, $nodeHeight = 100)
    {
        $imagePath = 'image/path';

        $image = $this->createImageMock(self::IMAGE_WIDTH, self::IMAGE_HEIGHT);        
        $document = $this->createDocumentMock($imagePath, $image);
               
        $x = 0;
        $y = $nodeHeight;

        $width = rand(10, 50);
        $height = rand(10, 50);
        
        $document->expects($this->at(1))
                 ->method('convertUnit')
                 ->with($width)
                 ->will($this->returnValue($imageWidth));
        $document->expects($this->at(2))
                 ->method('convertUnit')
                 ->with($height)
                 ->will($this->returnValue($imageHeight));
        $enhancement = new Background(null, $imagePath, Background::REPEAT_NONE, null, false, $width, $height);
        
        $gcMock = $this->getMockBuilder('PHPPdf\Engine\GraphicsContext')
        			   ->getMock();

        $nodeMock = $this->getNodeMock($x, $y, $nodeWidth, $nodeHeight, $gcMock);
        
        $gcMock->expects($this->once())
               ->method('drawImage')
               ->with($image, $x, $y-$expectedVertiacalTranslation, $x+$expectedHorizontalTranslation, $y);
               
        $enhancement->enhance($nodeMock, $document);
    }
    
    public function imageDimensionProvider()
    {
        return array(
            array(self::IMAGE_WIDTH / 2, null, self::IMAGE_WIDTH / 2, self::IMAGE_HEIGHT / 2),
            array(null, self::IMAGE_HEIGHT / 2, self::IMAGE_WIDTH / 2, self::IMAGE_HEIGHT / 2),
            array('30%', null, 60, 60, 200, 200),
            array(null, '40%', 80, 80, 200, 200),
        );
    }
    
    /**
     * @test
     * @dataProvider positionProvider
     */
    public function drawImageAsBackgroundInProperPosition($positionX, $positionY, $expectedPositionX, $expectedPositionY)
    {
        $imagePath = 'image/path';

        $image = $this->createImageMock(self::IMAGE_WIDTH, self::IMAGE_HEIGHT);        
        $document = $this->createDocumentMock($imagePath, $image);
        
        $enhancement = new Background(null, $imagePath, Background::REPEAT_NONE, null, false, null, null, $positionX, $positionY);
        
        $gcMock = $this->getMockBuilder('PHPPdf\Engine\GraphicsContext')
        			   ->getMock();

        $x = 0;
        $y = self::IMAGE_HEIGHT*2;
        $nodeWidth = 100;
        $nodeHeight = $y;
        			   
        $nodeMock = $this->getNodeMock($x, $y, $nodeWidth, $nodeHeight, $gcMock);
        
        $gcMock->expects($this->once())
               ->method('drawImage')
               ->with($image, $expectedPositionX, $expectedPositionY - self::IMAGE_HEIGHT, $expectedPositionX+self::IMAGE_WIDTH, $expectedPositionY);
               
        $enhancement->enhance($nodeMock, $document);
    }
    
    public function positionProvider()
    {
        return array(
            array(Background::POSITION_LEFT, Background::POSITION_TOP, 0, 2*self::IMAGE_HEIGHT),
            array(Background::POSITION_RIGHT, Background::POSITION_TOP, 100 - self::IMAGE_WIDTH, 2*self::IMAGE_HEIGHT),
            array(Background::POSITION_CENTER, Background::POSITION_TOP, 50 - self::IMAGE_WIDTH/2, 2*self::IMAGE_HEIGHT),
            array(Background::POSITION_LEFT, Background::POSITION_BOTTOM, 0, self::IMAGE_HEIGHT),
            array(Background::POSITION_LEFT, Background::POSITION_CENTER, 0, 2*3/4*self::IMAGE_HEIGHT),
        );
    }
}