<?php

namespace PHPPdf\Test\Formatter;

use PHPPdf\Document,
    PHPPdf\Node\Image,
    PHPPdf\Formatter\ImageDimensionFormatter,
    PHPPdf\Node\Page,
    PHPPdf\Node\Container;

class ImageDimensionFormatterTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $formatter;
    private $document;

    public function setUp()
    {
        $this->formatter = new ImageDimensionFormatter();
        $this->document = new Document();
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
        
        $image = new Image(array(
            'src' => $imageResource,
        ));
        $page->add($image);
        
        $this->formatter->format($image, $this->document);

        $this->assertEquals($imageWidth, $image->getWidth());
        $this->assertEquals($imageHeight, $image->getHeight());
    }
    
    private function createImageResourceMock($width, $height)
    {
        $imageResource = $this->getMockBuilder('PHPPdf\Engine\Image')
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
        
        $imageResource = $this->getMockBuilder('PHPPdf\Engine\Image')
                              ->setMethods(array('getOriginalHeight', 'getOriginalWidth'))
                              ->getMock();
                              
        $imageResource->expects($this->atLeastOnce())
                      ->method('getOriginalHeight')
                      ->will($this->returnValue($height));
        $imageResource->expects($this->atLeastOnce())
                      ->method('getOriginalWidth')
                      ->will($this->returnValue($width));
        
        $image = new Image(array(
            'src' => $imageResource,
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
        
        $imageResource = $this->createImageResourceMock($imageWidth, $imageHeight);

        $image = new Image(array(
            'src' => $imageResource,
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