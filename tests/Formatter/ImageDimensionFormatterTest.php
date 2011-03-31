<?php

use PHPPdf\Document,
    PHPPdf\Glyph\Image,
    PHPPdf\Formatter\ImageDimensionFormatter,
    PHPPdf\Glyph\Page,
    PHPPdf\Glyph\Container;

class ImageDimensionFormatterTest extends TestCase
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
        $image = new Image(array(
            'src' => \Zend_Pdf_Image::imageWithPath(dirname(__FILE__).'/../resources/domek.jpg'),
        ));
        $page->add($image);
        
        $this->formatter->format($image, $this->document);

        $imageHeight = $image->getAttribute('src')->getPixelHeight();
        $imageWidth = $image->getAttribute('src')->getPixelWidth();

        $this->assertEquals($imageWidth, $image->getWidth());
        $this->assertEquals($imageHeight, $image->getHeight());
    }

    /**
     * @test
     */
    public function drawingInSmallerContainer()
    {
        $page = new Page();

        $imageResource = \Zend_Pdf_Image::imageWithPath(dirname(__FILE__).'/../resources/domek.jpg');
        $image = new Image(array(
            'src' => $imageResource,
        ));

        $container = new Container(array(
            'width' => (int) ($imageResource->getPixelWidth() * 0.7),
            'height' => (int) ($imageResource->getPixelHeight() * 0.5),
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

        $imageResource = \Zend_Pdf_Image::imageWithPath(dirname(__FILE__).'/../resources/zend.jpg');

        $image = new Image(array(
            'src' => $imageResource,
            'width' => $width,
            'height' => $height,
        ));
        $page->add($image);

        $this->formatter->format($image, $this->document);

        $ratio = $imageResource->getPixelWidth() / $imageResource->getPixelHeight();

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