<?php

namespace PHPPdf\Test\Engine\ZF;

use PHPPdf\Engine\ZF\Color;

class ColorTest extends \TestCase
{
    /**
     * @test
     */
    public function createColorObject()
    {
        $colorData = '#000000';
        
        $color = new Color($colorData);
        
        $this->assertEquals(array(0), $color->getComponents());
        
        $zendColor = $color->getWrappedColor();
        
        $this->assertInstanceOf('Zend_Pdf_Color', $zendColor);
        
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