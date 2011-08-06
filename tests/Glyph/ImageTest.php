<?php

use PHPPdf\Document;
use PHPPdf\Glyph\Image;

class Zend_Pdf_Page_Stub extends \Zend_Pdf_Page
{
    public function __construct()
    {
        parent::__construct(\Zend_Pdf_Page::SIZE_A4);
    }
}

class ImageTest extends PHPUnit_Framework_TestCase
{
    private $image;
    private $filePath;

    public function setUp()
    {
        $this->filePath = __DIR__.'/../resources/domek.jpg';
        $this->image = new Image(array(
            'src' => \Zend_Pdf_Image::imageWithPath($this->filePath),
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
        $pageMock = $this->getMock('PHPPdf\Glyph\Page', array('getGraphicsContext'));

        $imageResource = $this->image->getAttribute('src');

        $gcMock = $this->getMock('PHPPdf\Glyph\GraphicsContext', array('drawImage'), array(), '', false);

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