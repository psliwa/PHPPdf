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
        try
        {
            $imagine = new Imagine();
            $image = new Image(TEST_RESOURCES_DIR.'/domek.png', $imagine);
            
            $imagineImage = $image->getWrappedImage();
            
            $this->assertEquals($imagineImage->getSize()->getHeight(), $image->getOriginalHeight());
            $this->assertEquals($imagineImage->getSize()->getWidth(), $image->getOriginalWidth());
        }
        catch(\Imagine\Exception\RuntimeException $e)
        {
            if($e->getMessage() == 'Gd not installed')
            {
                $this->markTestSkipped($e->getMessage());
            }
            else
            {
                throw $e;
            }
        }
    }
    
    /**
     * @test
     * @expectedException PHPPdf\Exception\InvalidResourceException
     */
    public function throwExceptionOnUnexistedImage()
    {
        try
        {
            $imagine = new Imagine();
            $image = new Image('some path', $imagine);
            
            $image->getWrappedImage();
            
        }
        catch(\Imagine\Exception\RuntimeException $e)
        {
            if($e->getMessage() == 'Gd not installed')
            {
                $this->markTestSkipped($e->getMessage());
            }
            else
            {
                throw $e;
            }
        }
    }
}