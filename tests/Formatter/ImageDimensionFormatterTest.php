<?php

use PHPPdf\Document;
use PHPPdf\Glyph\Image;
use PHPPdf\Formatter\ImageDimensionFormatter;
use PHPPdf\Glyph\Page;
use PHPPdf\Glyph\Container;

class ImageDimensionFormatterTest extends PHPUnit_Framework_TestCase
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
        $boundary = $image->getBoundary();
        $boundary->setNext(0, $page->getHeight());
        
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
        $boundary = $image->getBoundary();
        $boundary->setNext(0, $page->getHeight());

        $container = new Container(array(
            'width' => (int) ($imageResource->getPixelWidth() * 0.7),
            'height' => (int) ($imageResource->getPixelHeight() * 0.5),
        ));
        $container->getBoundary()->setNext(0, $page->getHeight());

        $container->add($image);

        $this->formatter->format($image, $this->document);

        $this->assertEquals($container->getHeight(), $image->getHeight());
        $this->assertTrue($container->getWidth() > $image->getWidth());
    }

    /**
     * @test
     *
     * @todo obliczanie drugiego wymiaru, gdy ustawiony zostanie tylko jeden
     */
    public function calculateSecondSize()
    {
        $page = new Page();

        $imageResource = \Zend_Pdf_Image::imageWithPath(dirname(__FILE__).'/../resources/domek.jpg');

        $image = new Image(array(
            'src' => $imageResource,
            'width' => 100,
        ));
        $page->add($image);
        $image->getBoundary()->setNext(0, $page->getHeight());

        $this->formatter->format($image, $this->document);

        $excepted = $imageResource->getPixelWidth()/$imageResource->getPixelHeight() * $image->getWidth();
        $this->assertEquals($excepted, $image->getHeight());
    }
}