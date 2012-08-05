<?php

namespace PHPPdf\Test\Core\Engine\ZF;

use PHPPdf\Core\Engine\ZF\Image;
use PHPPdf\Core\UnitConverter;

class ImageTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        if(!class_exists('ZendPdf\PdfDocument', true))
        {
            $this->fail('Zend Framework 2 library is missing. You have to download dependencies, for example by using "vendors.php" file.');
        }
    }

    /**
     * @test
     */
    public function createImageObject()
    {
        $image = new Image(TEST_RESOURCES_DIR.'/domek.png');
        
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
        $converter = $this->getMockBuilder('PHPPdf\Core\UnitConverter')
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
                          
        $image = new Image(TEST_RESOURCES_DIR.'/domek.png', $converter);
        
        $this->assertEquals($size, $image->getOriginalWidth());        
        $this->assertEquals($size, $image->getOriginalHeight());        
    }
}