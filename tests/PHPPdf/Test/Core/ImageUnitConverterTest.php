<?php

namespace PHPPdf\Test\Core;

use PHPPdf\Core\ImageUnitConverter;
use PHPPdf\PHPUnit\Framework\TestCase;

class ImageUnitConverterTest extends TestCase
{    
    /**
     * @test
     * @dataProvider unitProvider
     */
    public function convertUnit($value, $expected, $unit = null, $dpi = 1)
    {
        $converter = new ImageUnitConverter($dpi);
               
        $this->assertEquals($expected, $converter->convertUnit($value, $unit), 'invalid unit conversion', 0.0001);
    }

    public function unitProvider()
    {
        return array(
            array('1px', 1, null, 1),
            array('1px', 1, null, 2),
            array('3', 3*96/ImageUnitConverter::UNITS_PER_INCH, null, 96),
            array('3in', 9, null, 3),
            array('10mm', 10*300/ImageUnitConverter::MM_PER_INCH, null, 300),
            array('10cm', 10*10*400/ImageUnitConverter::MM_PER_INCH, null, 400),
            array('4pt', 4*500/72, null, 500),
            array('4', 4*500/72, null, 500),
            array('4pc', 12*4*600/72, null, 600),
            array('1', 1, 'px', 1),
            array('1cm', 500/72, 'pt', 500),
            array(123, 123, null, 22),//integer values aren't converted
            array('22%', '22%', null, 123),//percentage values aren't converted
        );
    }
}