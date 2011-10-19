<?php

namespace PHPPdf\Test\Core\Engine\ZF;

use PHPPdf\Core\Engine\ZF\Color;

class ColorTest extends \PHPPdf\PHPUnit\Framework\TestCase
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