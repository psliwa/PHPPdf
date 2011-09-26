<?php

namespace PHPPdf\Test\Engine\ZF;

use PHPPdf\Engine\ZF\Image;
use PHPPdf\Util\UnitConverter;

class ImageTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function createImageObject()
    {
        $image = new Image(TEST_RESOURCES_DIR.'/domek.jpg');
        
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
    
    /**
     * @test
     */
    public function convertImageSizeByUnitConverter()
    {
        $converter = $this->getMockBuilder('PHPPdf\Util\UnitConverter')
                          ->getMock();
                          
        $size = 100;
        $sampleImageSize = 315;
        $converter->expects($this->at(0))
                  ->method('convertUnit')
                  ->with($sampleImageSize, UnitConverter::UNIT_PIXEL)
                  ->will($this->returnValue($size));
        $converter->expects($this->at(1))
                  ->method('convertUnit')
                  ->with($sampleImageSize, UnitConverter::UNIT_PIXEL)
                  ->will($this->returnValue($size));
                          
        $image = new Image(TEST_RESOURCES_DIR.'/domek.jpg', $converter);
        
        $this->assertEquals($size, $image->getOriginalWidth());        
        $this->assertEquals($size, $image->getOriginalHeight());        
    }
}