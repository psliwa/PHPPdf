<?php

namespace PHPPdf\Test\Core\Engine\Imagine;

use PHPPdf\Core\Engine\Imagine\Color;
use PHPPdf\PHPUnit\Framework\TestCase;

class ColorTest extends TestCase
{
    /**
     * @test
     * @dataProvider colorProvider
     */
    public function getComponents($r, $g, $b)
    {
        $colorMock = $this->getMockBuilder('Imagine\Image\Color')
                          ->setMethods(array('getRed', 'getGreen', 'getBlue'))
                          ->disableOriginalConstructor()
                          ->getMock();
                          
        $color = new Color($colorMock);
        
        $this->assertEquals(array($r, $g, $b), $color->getComponents());
    }
    
    public function colorProvider()
    {
        return array(
            array(100, 120, 130),
        );
    }
}