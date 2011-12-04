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
        $components = array($r, $g, $b);
        $imagineColor = new \Imagine\Image\Color($components);
                          
        $color = new Color($imagineColor);
        
        $this->assertEquals($components, $color->getComponents());
    }
    
    public function colorProvider()
    {
        return array(
            array(100, 120, 130),
        );
    }
}