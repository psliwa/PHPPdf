<?php

namespace PHPPdf\Test\Core\Node;

use PHPPdf\Exception\InvalidResourceException;

use PHPPdf\Core\DrawingTaskHeap;

use PHPPdf\Core\Document;
use PHPPdf\Core\Node\Image;

class ImageTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $image;

    public function setUp()
    {
        $this->image = new Image(array(
            'width' => 100,
            'height' => 100,
        ));

        $boundary = $this->image->getBoundary();
        $boundary->setNext(0, 100)
                 ->setNext(100, 100)
                 ->setNext(100, 0)
                 ->setNext(0, 0)
                 ->close();
    }
    
    /**
     * @test
     */
    public function drawing()
    {
        $imagePath = 'some/path';
        
        $this->image->setAttribute('src', $imagePath);
        
        $imageResource = $this->getMockBuilder('PHPPdf\Core\Engine\Image')
                              ->getMock();
        $document = $this->getMockBuilder('PHPPdf\Core\Document')
                         ->setMethods(array('createImage'))
                         ->disableOriginalConstructor()
                         ->getMock();

        $document->expects($this->atLeastOnce())
                 ->method('createImage')
                 ->with($imagePath)
                 ->will($this->returnValue($imageResource));
                 
        $pageMock = $this->getMock('PHPPdf\Core\Node\Page', array('getGraphicsContext'));      

        $gcMock = $this->getMockBuilder('PHPPdf\Core\Engine\GraphicsContext')
        			   ->getMock();

        $gcMock->expects($this->once())
               ->method('drawImage')
               ->with($imageResource, 0, 100-$this->image->getHeight(), 0 + $this->image->getWidth(), 100);

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
        $src = 'src';
        $this->image->setAttribute('ignore-error', $ignoreError);
        $this->image->setAttribute('src', $src);
        
        $engine = $this->getMock('PHPPdf\Core\Engine\Engine');
                         
        $mocker = $engine->expects($this->once())
                         ->method('createImage')
                         ->with($src);
                           
        if($invalidSrc)
        {
            $mocker->will($this->throwException(new InvalidResourceException()));
        }
        else
        {
            $engineImage = $this->getMock('PHPPdf\Core\Engine\Image');
            $mocker->will($this->returnValue($engineImage));
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
}