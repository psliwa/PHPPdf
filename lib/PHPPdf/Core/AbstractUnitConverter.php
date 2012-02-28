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
abstract class AbstractUnitConverter implements UnitConverter
{
    protected function doConvertUnit($value, $unit)
    {
        switch($unit)
        {
            case self::UNIT_PIXEL:
                return $this->convertPxUnit($value);
            case self::UNIT_CENTIMETER:
                return 10*$this->convertInUnit($value)/self::MM_PER_INCH;
            case self::UNIT_MILIMETER:
                return $this->convertInUnit($value)/self::MM_PER_INCH;
            case self::UNIT_INCH:
                return $this->convertInUnit($value);
            case self::UNIT_PDF:
            case self::UNIT_POINT:
                return $this->convertPtUnit($value);
            case self::UNIT_PICA:
                return 12*$this->convertPtUnit($value);
            case self::UNIT_EM:
            case self::UNIT_EX:
                throw new InvalidArgumentException(sprintf('"%s" unit is not supported.', $unit));
            default:
                return $value;
        }
    }
    
    abstract protected function convertPxUnit($value);

    abstract protected function convertInUnit($value);
       
    abstract protected function convertPtUnit($value);

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