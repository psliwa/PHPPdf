<?php

namespace PHPPdf\Test\Core\Node;

use PHPPdf\Exception\InvalidResourceException;

use PHPPdf\Core\DrawingTaskHeap;

use PHPPdf\Core\Document;
use PHPPdf\Core\Node\Image;

class ImageTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    const IMAGE_WIDTH = 100;
    const IMAGE_HEIGHT = 100;
    const IMAGE_X_COORD = 0;
    const IMAGE_Y_COORD = 100;
    const IMAGE_PATH = 'image/path';
    
    private $image;

    public function setUp()
    {
        $this->image = new Image(array(
            'width' => 100,
            'height' => 100,
        ));

        $boundary = $this->image->getBoundary();
        $boundary->setNext(self::IMAGE_X_COORD, self::IMAGE_Y_COORD)
                 ->setNext(self::IMAGE_WIDTH, self::IMAGE_Y_COORD)
                 ->setNext(self::IMAGE_WIDTH, self::IMAGE_Y_COORD - self::IMAGE_HEIGHT)
                 ->setNext(self::IMAGE_X_COORD, self::IMAGE_Y_COORD - self::IMAGE_HEIGHT)
                 ->close();
    }
    
    /**
     * @test
     * @dataProvider drawImageInExpectedPositionProvider
     */
    public function drawImageInExpectedPosition($keepRatio, $sourceWidth = self::IMAGE_WIDTH, $sourceHeight = self::IMAGE_HEIGHT)
    {
        $this->image->setAttribute('src', self::IMAGE_PATH);
        $this->image->setAttribute('keep-ratio', $keepRatio);
        
        $imageResource = $this->getMock('PHPPdf\Core\Engine\Image');
        $imageResource->expects($this->any())
                      ->method('getOriginalWidth')
                      ->will($this->returnValue($sourceWidth));
        $imageResource->expects($this->any())
                      ->method('getOriginalHeight')
                      ->will($this->returnValue($sourceHeight));
        
        $document = $this->getMockBuilder('PHPPdf\Core\Document')
                         ->setMethods(array('createImage'))
                         ->disableOriginalConstructor()
                         ->getMock();

        $document->expects($this->atLeastOnce())
                 ->method('createImage')
                 ->with(self::IMAGE_PATH)
                 ->will($this->returnValue($imageResource));
                 
        $pageMock = $this->getMock('PHPPdf\Core\Node\Page', array('getGraphicsContext'));      

        $gcMock = $this->getMockBuilder('PHPPdf\Core\Engine\GraphicsContext')
        			   ->getMock();

        $expectedXCoord = self::IMAGE_X_COORD;
        $expectedYCoord = self::IMAGE_Y_COORD;
        $expectedWidth = self::IMAGE_WIDTH;
        $expectedHeight = self::IMAGE_HEIGHT;
        
        $drawExpectation = $gcMock->expects($this->once())
                                  ->method('drawImage');
        
        if($keepRatio)
        {
            $sourceRatio = $sourceHeight / $sourceWidth;
            
            $gcMock->expects($this->once())
                   ->method('clipRectangle')
                   ->id('clipRectangleInvocation')
                   ->with($expectedXCoord, $expectedYCoord, $expectedXCoord + $expectedWidth, $expectedYCoord - $expectedHeight);

            $drawExpectation->after('clipRectangleInvocation');

            if($sourceRatio > 1)
            {
                $expectedHeight = $expectedWidth * $sourceRatio;
                $expectedYCoord += ($expectedHeight - self::IMAGE_HEIGHT)/2;
            }
            else
            {
                $expectedWidth = $expectedHeight/$sourceRatio;
                $expectedXCoord -= ($expectedWidth - self::IMAGE_WIDTH)/2;
            }
        }
        			   
        $drawExpectation->with($imageResource, $expectedXCoord, $expectedYCoord-$expectedHeight, $expectedXCoord + $expectedWidth, $expectedYCoord);

        $pageMock->expects($this->once())
                 ->method('getGraphicsContext')
                 ->will($this->returnValue($gcMock));

        $this->image->setParent($pageMock);

        $tasks = new DrawingTaskHeap();
        $this->image->collectOrderedDrawingTasks($document, $tasks);

        foreach($tasks as $task)
        {
            $task->invoke();
        }
    }
    
    public function drawImageInExpectedPositionProvider()
    {
        return array(
            array(false),
            array(true, 200, 100),
            array(true, 100, 200),
        );
    }
    
    /**
     * @test
     * @dataProvider dataProvider
     */
    public function minWidthOfImageIsWidthIncraseByHorizontalMargins($width, $marginLeft, $marginRight)
    {
        $this->image->setWidth($width);
        $this->image->setMarginLeft($marginLeft);
        $this->image->setMarginRight($marginRight);
        
        $expectedMinWidth = $width + $marginLeft + $marginRight;
        
        $this->assertEquals($expectedMinWidth, $this->image->getMinWidth());
    }
    
    public function dataProvider()
    {
        return array(
            array(100, 0, 0),
            array(100, 5, 6),
        );
    }
    
    /**
     * @test
     * @dataProvider handleImageExceptionWhenIgnoreErrorAttributeIsOnProvider
     */
    public function handleImageExceptionWhenIgnoreErrorAttributeIsOn($ignoreError, $invalidSrc)
    {
        $this->givenImageWithIgnoreError($ignoreError);
        
        $engine = $this->getMock('PHPPdf\Core\Engine\Engine');
        $engineImage = $this->getMock('PHPPdf\Core\Engine\Image');

        if($invalidSrc)
        {
            $this->expectsEngineCreateImageFailure($engine);
        }
        else
        {
            $this->expectsEngineCreateImageSuccess($engine, $engineImage);
        }

        try
        {
            $actualSource = $this->image->createSource($engine);
            
            if(!$ignoreError && $invalidSrc)
            {
                $this->fail('error shouldn\'t be ignored');
            }
            
            if($invalidSrc)
            {
                $this->assertInstanceOf('PHPPdf\Core\Engine\EmptyImage', $actualSource);
            }
            else
            {
                $this->assertEquals($engineImage, $actualSource);
            }
        }
        catch(InvalidResourceException $e)
        {
            if($ignoreError)
            {
                $this->fail('error should be ignored');
            }
        }
    }

    public function handleImageExceptionWhenIgnoreErrorAttributeIsOnProvider()
    {
        return array(
            array(false, true),
            array(false, false),
            array(true, true),
            array(true, false),
        );
    }

    protected function givenImageWithIgnoreError($ignoreError = true)
    {
        $this->image->setAttribute('ignore-error', $ignoreError);
        $this->image->setAttribute('src', self::IMAGE_PATH);
    }

    protected function expectsEngineCreateImageFailure($engine)
    {
        $engine->expects($this->once())
            ->method('createImage')
            ->with(self::IMAGE_PATH)
            ->will($this->throwException(new InvalidResourceException()));
    }

    protected function expectsEngineCreateImageSuccess($engine, $engineImage)
    {
        $engine->expects($this->once())
            ->method('createImage')
            ->with(self::IMAGE_PATH)
            ->will($this->returnValue($engineImage));
    }

    /**
     * @test
     */
    public function turnOnIgnoreErrorAttribute_errorOccursOnPreFormat_ignoreError()
    {
        $this->givenImageWithIgnoreError();
        $this->clearImageSize();

        $document = $this->createDocumentMock();
        $this->expectsEngineCreateImageFailure($document);

        $this->image->preFormat($document);
    }

    /**
     * @test
     * @expectedException PHPPdf\Exception\InvalidResourceException
     */
    public function turnOffIgnoreErrorAttribute_errorOccursOnPreFormat_throwException()
    {
        $this->givenImageWithIgnoreError(false);
        $this->clearImageSize();

        $document = $this->createDocumentMock();
        $this->expectsEngineCreateImageFailure($document);

        $this->image->preFormat($document);
    }

    protected function createDocumentMock()
    {
        $document = $this->getMockBuilder('PHPPdf\Core\Document')
            ->setMethods(array('createImage'))
            ->disableOriginalConstructor()
            ->getMock();
        return $document;
    }

    protected function clearImageSize()
    {
        $this->image->setWidth(null);
        $this->image->setHeight(null);
    }
}