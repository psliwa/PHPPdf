<?php

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Formatter\ImageConvertAttributesFormatter;
use PHPPdf\Core\Document,
    PHPPdf\Core\Node\Image,
    PHPPdf\Core\Node\Page,
    PHPPdf\Core\Node\Container;

class ImageConvertAttributesFormatterTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $formatter;
    private $document;

    public function setUp()
    {
        $this->formatter = new ImageConvertAttributesFormatter();
        $this->document = $this->getMockBuilder('PHPPdf\Core\Document')
                               ->setMethods(array('createImage'))
                               ->disableOriginalConstructor()
                               ->getMock();
    }

    /**
     * @test
     */
    public function drawingFromBeginingOfThePage()
    {
        $page = new Page();
        
        $imageHeight = 100;
        $imageWidth = 50;
        
        $imageResource = $this->createImageResourceMock($imageWidth, $imageHeight);
        $imagePath = 'some/path';
        
        $image = new Image(array(
            'src' => $imagePath,
        ));
        $this->document->expects($this->atLeastOnce())
                       ->method('createImage')
                       ->with($imagePath)
                       ->will($this->returnValue($imageResource));

        $page->add($image);
        
        $this->formatter->format($image, $this->document);

        $this->assertEquals($imageWidth, $image->getWidth());
        $this->assertEquals($imageHeight, $image->getHeight());
    }
    
    private function createImageResourceMock($width, $height)
    {
        $imageResource = $this->getMockBuilder('PHPPdf\Core\Engine\Image')
                              ->setMethods(array('getOriginalHeight', 'getOriginalWidth'))
                              ->getMock();
        $imageResource->expects($this->atLeastOnce())
                      ->method('getOriginalHeight')
                      ->will($this->returnValue($height));
        $imageResource->expects($this->atLeastOnce())
                      ->method('getOriginalWidth')
                      ->will($this->returnValue($width));
                      
        return $imageResource;
    }

    /**
     * @test
     */
    public function drawingInSmallerContainer()
    {
        $page = new Page();

        $height = 100;
        $width = 120;
        $imagePath = 'image/path';
        
        $imageResource = $this->createImageResourceMock($width, $height);
        
        $this->document->expects($this->atLeastOnce())
                       ->method('createImage')
                       ->with($imagePath)
                       ->will($this->returnValue($imageResource));
                      
        $image = new Image(array(
            'src' => $imagePath,
        ));

        $container = new Container(array(
            'width' => (int) ($width * 0.7),
            'height' => (int) ($height * 0.5),
        ));

        $container->add($image);

        $this->formatter->format($image, $this->document);

        $this->assertEquals($container->getHeight(), $image->getHeight());
        $this->assertTrue($container->getWidth() > $image->getWidth());
    }

    /**
     * @test
     * @dataProvider sizeProvider
     */
    public function calculateSecondSize($width, $height)
    {
        $page = new Page();

        $imageWidth = 100;
        $imageHeight = 120;
        $imagePath = 'image/path';
        
        $imageResource = $this->createImageResourceMock($imageWidth, $imageHeight);

        $this->document->expects($this->atLeastOnce())
                       ->method('createImage')
                       ->with($imagePath)
                       ->will($this->returnValue($imageResource));
        

        $image = new Image(array(
            'src' => $imagePath,
            'width' => $width,
            'height' => $height,
        ));
        $page->add($image);

        $this->formatter->format($image, $this->document);

        $ratio = $imageWidth / $imageHeight;

        if(!$height)
        {
            $ratio = 1/$ratio;
        }

        $excepted = $ratio * ($width ? $width : $height);

        $this->assertEquals($excepted, $width ? $image->getHeight() : $image->getWidth());
    }

    public function sizeProvider()
    {
        return array(
            array(100, null),
            array(null, 100),
        );
    }
}