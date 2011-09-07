<?php

namespace PHPPdf\Test\Node;

use PHPPdf\Document;
use PHPPdf\Node\Image;

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
        $pageMock = $this->getMock('PHPPdf\Node\Page', array('getGraphicsContext'));

        $imageResource = $this->getMockBuilder('PHPPdf\Engine\Image')
                              ->getMock();
        $this->image->setAttribute('src', $imageResource);

        $gcMock = $this->getMockBuilder('PHPPdf\Engine\GraphicsContext')
        			   ->getMock();

        $gcMock->expects($this->once())
               ->method('drawImage')
               ->with($imageResource, 0, 100-$this->image->getHeight(), 0 + $this->image->getWidth(), 100);

        $pageMock->expects($this->once())
                 ->method('getGraphicsContext')
                 ->will($this->returnValue($gcMock));

        $document = new Document();

        $this->image->setParent($pageMock);

        $tasks = $this->image->getDrawingTasks(new Document());

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
}