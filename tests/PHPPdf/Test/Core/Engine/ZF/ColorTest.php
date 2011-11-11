<?php

namespace PHPPdf\Test\Core\Engine\ZF;

use PHPPdf\Core\Engine\ZF\Color;

class ColorTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        if(!class_exists('Zend\Pdf\PdfDocument', true))
        {
            $this->fail('Zend Framework 2 library is missing. You have to download dependencies, for example by using "vendors.php" file.');
        }
    }
    
    /**
     * @test
     */
    public function createColorObject()
    {
        $colorData = '#000000';
        
        $color = new Color($colorData);
        
        $this->assertEquals(array(0), $color->getComponents());
        
        $zendColor = $color->getWrappedColor();
        
        $this->assertInstanceOf('Zend\Pdf\Color', $zendColor);
        
        $this->assertEquals($color->getComponents(), $zendColor->getComponents());
    }
    
    /**
     * @test
     * @expectedException PHPPdf\Exception\InvalidResourceException
     */
    public function throwExceptionOnInvalidColorData()
    {
        $color = new Color('invalid');
    }
}