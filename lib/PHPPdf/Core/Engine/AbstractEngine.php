<?php

namespace PHPPdf\Core\Engine;

use PHPPdf\Core\UnitConverter;

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
        
        return $value;
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