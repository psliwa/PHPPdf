<?php

namespace PHPPdf\Test\Util;

use PHPPdf\Util\UnitConverterImpl;
use PHPPdf\PHPUnit\Framework\TestCase;

class UnitConverterImplTest extends TestCase
{
    /**
     * @test
     * @dataProvider percentageValuesProvider
     */
    public function convertPercentageValue($percent, $value, $expected)
    {
        $converter = new UnitConverterImpl();
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
    public function convertUnit($unit, $expected, $dpi = 1)
    {
        $converter = new UnitConverterImpl($dpi);
        
        $this->assertEquals($expected, $converter->convertUnit($unit), 'invalid unit conversion', 0.001);
    }

    public function unitProvider()
    {
        return array(
            array('1px', 25.4, 1),
            array('1px', 25.4/2, 2),
            array(3, 3, 12),
            array(3.2, 3.2, 12),
            array('10cm', 100),
            array('10mm', 10),
            array('10em', 10),
            array('1in', 25.4),
            array('1pt', 25.4/72),
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
        
        $converter = new UnitConverterImpl($dpi);
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