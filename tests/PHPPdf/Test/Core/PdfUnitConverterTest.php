<?php

namespace PHPPdf\Test\Core;

use PHPPdf\Core\PdfUnitConverter;
use PHPPdf\PHPUnit\Framework\TestCase;

class PdfUnitConverterTest extends TestCase
{
    /**
     * @test
     * @dataProvider percentageValuesProvider
     */
    public function convertPercentageValue($percent, $value, $expected)
    {
        $converter = new PdfUnitConverter();
        $this->assertEquals($expected, $converter->convertPercentageValue($percent, $value));
    }
    
    public function percentageValuesProvider()
    {
        return array(
            array('100%', 30, 30),
            array(100, 30, 100),
            array('50%', 30, 15),
        );
    }
    
    /**
     * @test
     * @dataProvider unitProvider
     */
    public function convertUnit($value, $expected, $unit = null, $dpi = 1)
    {
        $converter = new PdfUnitConverter($dpi);
        
        $this->assertEquals($expected, $converter->convertUnit($value, $unit), 'invalid unit conversion', 0.001);
    }

    public function unitProvider()
    {
        return array(
            array('1px', PdfUnitConverter::UNITS_PER_INCH, null, 1),
            array('1px', PdfUnitConverter::UNITS_PER_INCH/2, null, 2),
            array(3, 3, 12),
            array(3.2, 3.2, 12),
            array('10cm', 10*72/PdfUnitConverter::MM_PER_INCH*10),
            array('10mm', 10*72/PdfUnitConverter::MM_PER_INCH),
            array('1in', PdfUnitConverter::UNITS_PER_INCH),
            array('1pt', 1),
            array('1pc', 12),
            array(1, PdfUnitConverter::UNITS_PER_INCH, 'px', 1),
            array('1pt', PdfUnitConverter::UNITS_PER_INCH, 'px', 1),
            array('22%', '22%', null, 123),
        );
    }
    
    /**
     * @test
     * @dataProvider dpiProvider
     */
    public function dpiMustBePositiveInteger($dpi, $expectedException)
    {
        if($expectedException)
        {
            $this->setExpectedException('InvalidArgumentException');
        }
        
        $converter = new PdfUnitConverter($dpi);
    }
    
    public function dpiProvider()
    {
        return array(
            array(10, false),
            array(0, true),
            array(-5, true),
            array(2.4, true),
            array(2, false),
        );
    }
}