<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Engine;

use PHPPdf\Core\UnitConverter;

/**
 * Abstract engine
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
abstract class AbstractEngine implements Engine
{
    protected $unitConverter;
    
    public function __construct(UnitConverter $unitConverter = null)
    {
        $this->unitConverter = $unitConverter;
    }
    
    public function convertUnit($value, $unit = null)
    {
        if($this->unitConverter)
        {
            return $this->unitConverter->convertUnit($value, $unit);
        }

        return (int) $value;
    }

    public function convertPercentageValue($percent, $value)
    {
        if($this->unitConverter)
        {
            return $this->unitConverter->convertPercentageValue($percent, $value);
        }
        
        return $value;
    }
}