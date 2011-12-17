<?php

namespace PHPPdf\Test\Core;

use PHPPdf\Core\ColorPalette;

use PHPPdf\PHPUnit\Framework\TestCase;

class ColorPaletteTest extends TestCase
{
    /**
     * @test
     */
    public function createColors()
    {
        $colors = array(
            'blue' => '#0000ff',
            'black' => '#000000',
        );
        
        $palette = new ColorPalette($colors);
        
        foreach($colors as $name => $expectedColor)
        {
            foreach(array($name, strtoupper($name)) as $key)
            {
                $actualColor = $palette->get($key);
                $this->assertEquals($expectedColor, $actualColor);
            }
        }
        
        $hexColors = array('#000000', '#ffffff');
        
        foreach($hexColors as $color)
        {
            $this->assertEquals($color, $palette->get($color));
        }
    }
    
    /**
     * @test
     */
    public function mergeColors()
    {
        $colors = array(
            'blue' => '#0000ff',
            'black' => '#000000',
        );
        
        $palette = new ColorPalette($colors);
        
        $newColors = array(
            'red' => '#ff0000',
            'green' => '#00ff00',
        );
        
        $palette->merge($newColors);
        
        $expectedColors = array_merge($colors, $newColors);
        
        $this->assertEquals($expectedColors, $palette->getAll());
    }
}