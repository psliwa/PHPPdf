<?php

namespace PHPPdf\Test\Core\Engine\Imagine;

use PHPPdf\Core\Engine\Imagine\Image;
use Imagine\Gd\Imagine;
use PHPPdf\PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
    /**
     * @test
     */
    public function createImageObject()
    {
        $imagine = new Imagine();
        $image = new Image(TEST_RESOURCES_DIR.'/domek.png', $imagine);
        
        $imagineImage = $image->getWrappedImage();
        
        $this->assertEquals($imagineImage->getSize()->getHeight(), $image->getOriginalHeight());
        $this->assertEquals($imagineImage->getSize()->getWidth(), $image->getOriginalWidth());
    }
    
    /**
     * @test
     * @expectedException PHPPdf\Exception\InvalidResourceException
     */
    public function throwExceptionOnUnexistedImage()
    {
        $imagine = new Imagine();
        $image = new Image('some path', $imagine);
        
        $image->getWrappedImage();
    }
}