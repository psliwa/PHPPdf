<?php

use PHPPdf\Document;
use PHPPdf\Enhancement\Background,
    PHPPdf\Glyph\Page,
    PHPPdf\Util\Point;

class BackgroundTest extends TestCase
{
    const IMAGE_WIDTH = 30;
    const IMAGE_HEIGHT = 30;
    
    private $imagePath;
    private $objectMother;
    private $document;

    public function init()
    {
        $this->objectMother = new GenericGlyphObjectMother($this);
    }

    public function setUp()
    {
        $this->imagePath = __DIR__.'/../resources/domek-min.jpg';
        $this->document = new Document();
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

        $glyphMock = $this->getGlyphMock($x, $y, $width, $height, $gcMock);

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

        $background->enhance($glyphMock, $document);
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
                         ->setMethods(array('createImage'))
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

        $glyphMock = $this->getGlyphMock($x, $y, $width, $height, $gcMock);

        $gcMock->expects($this->once())
               ->method('saveGS');

        $gcMock->expects($this->once())
               ->method('clipRectangle')
               ->with($x, $y, $x+$width, $y-$height);

        $gcMock->expects($this->exactly($count))
               ->method('drawImage');

        $gcMock->expects($this->once())
               ->method('restoreGS');


        $background->enhance($glyphMock, $document);
    }

    public function kindOfBackgroundsProvider()
    {
        return array(
            array(Background::REPEAT_X),
            array(Background::REPEAT_Y),
            array(Background::REPEAT_ALL),
        );
    }

    private function getGlyphMock($x, $y, $width, $height, $gcMock)
    {
        $boundaryMock = $this->getBoundaryStub($x, $y, $width, $height);

        $glyphMock = $this->getMock('PHPPdf\Glyph\Glyph', array('getBoundary', 'getWidth', 'getHeight', 'getGraphicsContext'));
        $glyphMock->expects($this->atLeastOnce())
                  ->method('getBoundary')
                  ->will($this->returnValue($boundaryMock));
        $glyphMock->expects($this->any())
                  ->method('getWidth')
                  ->will($this->returnValue($width));

        $glyphMock->expects($this->any())
                  ->method('getHeight')
                  ->will($this->returnValue($height));

        $glyphMock->expects($this->atLeastOnce())
                  ->method('getGraphicsContext')
                  ->will($this->returnValue($gcMock));

        return $glyphMock;
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
               ->with(0, 70, 50, 100, $radius, Zend_Pdf_Page::SHAPE_DRAW_FILL_AND_STROKE);

        $this->addStandardExpectationsToGraphicContext($gcMock);

        $glyphMock = $this->getGlyphMock(0, 100, 50, 30, $gcMock);

        $border = new Background('black', null, Background::REPEAT_ALL, $radius);

        $border->enhance($glyphMock, $this->document);
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
        
        $glyph = $this->getMockBuilder('PHPPdf\Glyph\Container')
                      ->setMethods(array('getRealBoundary', 'getBoundary', 'getGraphicsContext'))
                      ->getMock();

        $height = 100;
        $width = 100;        
        $boundary = $this->getBoundaryStub(0, 100, $width, $height);
                      
        $glyph->expects($this->atLeastOnce())
              ->method('getRealBoundary')
              ->will($this->returnValue($boundary));

        $glyph->expects($this->never())
              ->method('getBoundary');
                     
        $gc = $this->getMockBuilder('PHPPdf\Engine\GraphicsContext')
                   ->getMock();
                   
        $expectedXCoords = array(
            $boundary[0]->getX() - 0.5,
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
            $boundary[0]->getY() + 0.5,
        );

        $gc->expects($this->once())
           ->method('drawPolygon')
           ->with($expectedXCoords, $expectedYCoords, $this->anything());
                     
        $glyph->expects($this->atLeastOnce())
              ->method('getGraphicsContext')
              ->will($this->returnValue($gc));
             
        $enhancement->enhance($glyph, $this->document);
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
    public function useBackgrounImageDimension($imageWidth, $imageHeight, $expectedHorizontalTranslation, $expectedVertiacalTranslation, $glyphWidth = 100, $glyphHeight = 100)
    {
        $imagePath = 'image/path';

        $image = $this->createImageMock(self::IMAGE_WIDTH, self::IMAGE_HEIGHT);        
        $document = $this->createDocumentMock($imagePath, $image);
               
        $x = 0;
        $y = $glyphHeight;

        
        $enhancement = new Background(null, $imagePath, Background::REPEAT_NONE, null, false, $imageWidth, $imageHeight);
        
        $gcMock = $this->getMockBuilder('PHPPdf\Engine\GraphicsContext')
        			   ->getMock();

        $glyphMock = $this->getGlyphMock($x, $y, $glyphWidth, $glyphHeight, $gcMock);
        
        $gcMock->expects($this->once())
               ->method('drawImage')
               ->with($image, $x, $y-$expectedVertiacalTranslation, $x+$expectedHorizontalTranslation, $y);
               
        $enhancement->enhance($glyphMock, $document);
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
}