<?php

namespace PHPPdf\Test\Engine\ZF;

use PHPPdf\Engine\ZF\Image;

class ImageTest extends \TestCase
{
    /**
     * @test
     */
    public function createImageObject()
    {
        $image = new Image(__DIR__.'/../../resources/domek.jpg');
        
        $zendImage = $image->getWrappedImage();
        
        $this->assertEquals($zendImage->getPixelHeight(), $image->getOriginalHeight());
        $this->assertEquals($zendImage->getPixelWidth(), $image->getOriginalWidth());
    }
    
    /**
     * @test
     * @expectedException PHPPdf\Exception\InvalidResourceException
     */
    public function throwExceptionOnUnexistedImage()
    {
        $image = new Image('some path');
    }
}