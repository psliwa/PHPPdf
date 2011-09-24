<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Util;

/**
 * Unit converter
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class UnitConverterImpl implements UnitConverter
{
    //unit of x and y axes is 1/72 inch
    const UNITS_PER_INCH = 72;

    private $dpi;
    
    public function __construct($dpi = 96)
    {
        if(!is_int($dpi) || $dpi < 1)
        {
            throw new \InvalidArgumentException(sprintf('Dpi must be positive integer, "%s" given.', $dpi));
        }

        $this->dpi = $dpi;
    }
    
    public function convertUnit($value)
    {
        if(is_numeric($value))
        {
            return $value;
        }
        
        $unit = strtolower(substr($value, -2, 2));

        return $this->doConvertUnit($value, $unit);
    }
    
    private function doConvertUnit($value, $unit)
    {
        switch($unit)
        {
            case 'px':
                return $this->convertPxUnit($value);
            case 'cm':
                return $this->convertCmUnit($value);
            case 'mm':
                return $this->convertMmUnit($value);
            case 'in':
                return $this->convertInUnit($value);
            case 'pt':
                return $this->convertPtUnit($value);
            case 'pc':
                return 12*$this->convertPtUnit($value);
            case 'em':
            case 'ex':
                throw new \InvalidArgumentException(sprintf('"%s" unit is not supported.', $unit));
            default:
                return $value;
        }
    }

    private function convertPxUnit($value)
    {
        $value = (float) $value;
        return $value * self::UNITS_PER_INCH/$this->dpi;
    }

    private function convertCmUnit($value)
    {
        $value = (float) $value;
        
        return $value * 10;
    }

    private function convertInUnit($value)
    {
        return ((float) $value)*self::UNITS_PER_INCH;
    }
       
    private function convertPtUnit($value)
    {
        $value = (float) $value;
        return $value * self::UNITS_PER_INCH/72;
    }
    
    private function convertMmUnit($value)
    {
        return (float) $value;
    }

    public function convertPercentageValue($percent, $value)
    {
        if(strpos($percent, '%') !== false)
        {
            $percent = (double) $percent;
            $percent = $value*$percent / 100;
        }
        return $percent;
    }
}