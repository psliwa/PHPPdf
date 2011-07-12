<?php

use PHPPdf\Enhancement\Background,
    PHPPdf\Glyph\Page,
    PHPPdf\Util\Point;

class BackgroundTest extends TestCase
{
    const IMAGE_WIDTH = 30;
    const IMAGE_HEIGHT = 30;
    
    private $imagePath;
    private $objectMother;

    public function init()
    {
        $this->objectMother = new GenericGlyphObjectMother($this);
    }

    public function setUp()
    {
        $this->imagePath = __DIR__.'/../resources/domek-min.jpg';
    }

    /**
     * @test
     */
    public function backgroundWithoutRepeat()
    {
        $background = new Background(null, $this->imagePath);

        $x = 0;
        $y = 200;
        $width = $height = 100;
        
        $glyphMock = $this->getGlyphMock($x, $y, $width, $height);

        $gcMock = $this->getMock('PHPPdf\Glyph\GraphicsContext', array('drawImage', 'clipRectangle', 'saveGS', 'restoreGS'), array(), '', false);

        $pageMock = $this->getMock('PHPPdf\Glyph\Page', array('getGraphicsContext'));

        $pageMock->expects($this->atLeastOnce())
                 ->method('getGraphicsContext')
                 ->will($this->returnValue($gcMock));

        $gcMock->expects($this->at(0))
               ->method('saveGS');

        $gcMock->expects($this->at(1))
               ->method('clipRectangle')
               ->with($x, $y, $x+$width, $y-$height);

        $gcMock->expects($this->at(2))
               ->method('drawImage')
               ->with($background->getImage(), $x, $y-$background->getImage()->getPixelHeight(), $x+$background->getImage()->getPixelWidth(), $y);

        $gcMock->expects($this->at(3))
               ->method('restoreGS');

        $background->enhance($pageMock, $glyphMock);
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

        $image = \Zend_Pdf_Image::imageWithPath($this->imagePath);
        $background = new Background(null, $image, $repeat);

        $x = 1;
        if($repeat & Background::REPEAT_X)
        {
            $x = ceil($width / $image->getPixelWidth());
        }

        $y = 1;
        if($repeat & Background::REPEAT_Y)
        {
            $y = ceil($height / $image->getPixelHeight());
        }

        $count = (int) ($x*$y);

        $gcMock = $this->getMock('PHPPdf\Glyph\GraphicsContext', array('drawImage', 'clipRectangle', 'saveGS', 'restoreGS'), array(), '', false);

        $pageMock = $this->getMock('PHPPdf\Glyph\Page', array('getGraphicsContext'));

        $pageMock->expects($this->atLeastOnce())
                 ->method('getGraphicsContext')
                 ->will($this->returnValue($gcMock));

        $gcMock->expects($this->once())
               ->method('saveGS');

        $gcMock->expects($this->once())
               ->method('clipRectangle')
               ->with($x, $y, $x+$width, $y-$height);

        $gcMock->expects($this->exactly($count))
               ->method('drawImage');

        $gcMock->expects($this->once())
               ->method('restoreGS');

        $glyphMock = $this->getGlyphMock($x, $y, $width, $height);

        $background->enhance($pageMock, $glyphMock);
    }

    public function kindOfBackgroundsProvider()
    {
        return array(
            array(Background::REPEAT_X),
            array(Background::REPEAT_Y),
            array(Background::REPEAT_ALL),
        );
    }

    private function getGlyphMock($x, $y, $width, $height)
    {
        $boundaryMock = $this->getBoundaryStub($x, $y, $width, $height);

        $glyphMock = $this->getMock('PHPPdf\Glyph\Glyph', array('getBoundary', 'getWidth', 'getHeight'));
        $glyphMock->expects($this->atLeastOnce())
                  ->method('getBoundary')
                  ->will($this->returnValue($boundaryMock));
        $glyphMock->expects($this->any())
                  ->method('getWidth')
                  ->will($this->returnValue($width));

        $glyphMock->expects($this->any())
                  ->method('getHeight')
                  ->will($this->returnValue($height));

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

        $gcMock = $this->getMock('PHPPdf\Glyph\GraphicsContext', array('drawRoundedRectangle', 'saveGS', 'restoreGS', 'setLineColor', 'setFillColor'), array(), '', false);
        $gcMock->expects($this->once())
               ->method('drawRoundedRectangle')
               ->with(0, 70, 50, 100, $radius, Zend_Pdf_Page::SHAPE_DRAW_FILL_AND_STROKE);

        $this->addStandardExpectationsToGraphicContext($gcMock);

        $pageMock = $this->objectMother->getEmptyPageMock($gcMock);

        $glyphMock = $this->objectMother->getGlyphMock(0, 100, 50, 30);

        $border = new Background('black', null, Background::REPEAT_ALL, $radius);

        $border->enhance($pageMock, $glyphMock);
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
                      ->setMethods(array('getRealBoundary', 'getBoundary'))
                      ->getMock();

        $height = 100;
        $width = 100;        
        $boundary = $this->getBoundaryStub(0, 100, $width, $height);
                      
        $glyph->expects($this->atLeastOnce())
              ->method('getRealBoundary')
              ->will($this->returnValue($boundary));

        $glyph->expects($this->never())
              ->method('getBoundary');
        
        $page = $this->getMockBuilder('PHPPdf\Glyph\Page')
                     ->setMethods(array('getGraphicsContext'))
                     ->getMock();
                     
        $gc = $this->getMockBuilder('PHPPdf\Glyph\GraphicsContext')
                   ->disableOriginalConstructor()
                   ->disableOriginalClone()
                   ->setMethods(array('drawPolygon', 'saveGS', 'restoreGS', 'setLineColor', 'setFillColor'))
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
                     
        $page->expects($this->atLeastOnce())
             ->method('getGraphicsContext')
             ->will($this->returnValue($gc));
             
        $enhancement->enhance($page, $glyph);
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
        $image = \Zend_Pdf_Image::imageWithPath($this->imagePath);
               
        $x = 0;
        $y = $glyphHeight;

        $glyphMock = $this->getGlyphMock($x, $y, $glyphWidth, $glyphHeight);
        
        $enhancement = new Background(null, $image, Background::REPEAT_NONE, null, false, $imageWidth, $imageHeight);
        
        $gcMock = $this->getMockBuilder('PHPPdf\Glyph\GraphicsContext')
                       ->setMethods(array('drawImage', 'clipRectangle', 'saveGS', 'restoreGS'))
                       ->disableOriginalConstructor()
                       ->getMock();

        $pageMock = $this->getMock('PHPPdf\Glyph\Page', array('getGraphicsContext'));

        $pageMock->expects($this->atLeastOnce())
                 ->method('getGraphicsContext')
                 ->will($this->returnValue($gcMock));
        
        $gcMock->expects($this->once())
               ->method('drawImage')
               ->with($enhancement->getImage(), $x, $y-$expectedVertiacalTranslation, $x+$expectedHorizontalTranslation, $y);
               
        $enhancement->enhance($pageMock, $glyphMock);
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