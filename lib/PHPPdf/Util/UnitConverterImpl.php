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
    //unit of x and y axes id 1/72 inch
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
        
        if($this->isUnit($value, 'px'))
        {
            $value = $this->convertPxUnit($value);
        }
        elseif($this->isUnit($value, 'cm'))
        {
            $value = $this->convertCmUnit($value);
        }
        elseif($this->isUnit($value, 'in'))
        {
            $value = $this->convertInUnit($value);
        }
        elseif($this->isUnit($value, 'pt'))
        {
            $value = $this->convertPtUnit($value);
        }
        elseif($this->isUnit($value, 'mm') || $this->isUnit($value, 'em'))
        {
            $value = $this->convertMmUnit($value);
        }

        return $value;
    }
    
    private function isUnit($value, $unit)
    {
        return strpos($value, $unit) !== false;
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