<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core;

use PHPPdf\Exception\InvalidArgumentException;

/**
 * Unit converter
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class PdfUnitConverter extends AbstractUnitConverter
{
    private $dpi;
    private $unitsPerPixel;
    
    public function __construct($dpi = 96)
    {
        if(!is_int($dpi) || $dpi < 1)
        {
            throw new InvalidArgumentException(sprintf('Dpi must be positive integer, "%s" given.', $dpi));
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

    protected function convertPxUnit($value)
    {
        $value = (float) $value;
        return $value * $this->unitsPerPixel;
    }

    protected function convertInUnit($value)
    {
        return ((float) $value)*self::UNITS_PER_INCH;
    }
       
    protected function convertPtUnit($value)
    {
        return (float) $value;
    }
    
    protected function convertMmUnit($value)
    {
        return $this->convertInUnit($value)/self::MM_PER_INCH;
    }
}