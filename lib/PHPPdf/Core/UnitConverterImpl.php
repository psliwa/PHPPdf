<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core;

/**
 * Unit converter
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class UnitConverterImpl implements UnitConverter
{
    //unit of x and y axes is 1/72 inch
    const UNITS_PER_INCH = 72;
    const MM_PER_INCH = 25.3995;

    private $dpi;
    private $unitsPerPixel;
    
    public function __construct($dpi = 96)
    {
        if(!is_int($dpi) || $dpi < 1)
        {
            throw new \InvalidArgumentException(sprintf('Dpi must be positive integer, "%s" given.', $dpi));
        }

        $this->dpi = $dpi;
        $this->unitsPerPixel = self::UNITS_PER_INCH/$this->dpi;
    }
    
    public function convertUnit($value, $unit = null)
    {
        if(is_numeric($value) && $unit === null)
        {
            return $value;
        }
        
        $unit = $unit ? : strtolower(substr($value, -2, 2));

        return $this->doConvertUnit($value, $unit);
    }
    
    private function doConvertUnit($value, $unit)
    {
        switch($unit)
        {
            case self::UNIT_PIXEL:
                return $this->convertPxUnit($value);
            case self::UNIT_CENTIMETER:
                return $this->convertCmUnit($value);
            case self::UNIT_MILIMETER:
                return $this->convertMmUnit($value);
            case self::UNIT_INCH:
                return $this->convertInUnit($value);
            case self::UNIT_POINT:
                return $this->convertPtUnit($value);
            case self::UNIT_PICA:
                return 12*$this->convertPtUnit($value);
            case self::UNIT_EM:
            case self::UNIT_EX:
                throw new \InvalidArgumentException(sprintf('"%s" unit is not supported.', $unit));
            default:
                return $value;
        }
    }

    private function convertPxUnit($value)
    {
        $value = (float) $value;
        return $value * $this->unitsPerPixel;
    }

    private function convertCmUnit($value)
    {
        return $this->convertMmUnit($value)*10;
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
        return $this->convertInUnit($value)/self::MM_PER_INCH;
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