<?php

namespace PHPPdf\Core;

class ImageUnitConverter extends AbstractUnitConverter
{
    private $pixelPerUnits;
    private $dpi;
    
    public function __construct($dpi = 96)
    {
        if(!is_int($dpi) || $dpi < 1)
        {
            throw new \InvalidArgumentException(sprintf('Dpi must be positive integer, "%s" given.', $dpi));
        }

        $this->dpi = $dpi;
        $this->pixelPerUnits = $this->dpi/self::UNITS_PER_INCH;
    }
    
	public function convertUnit($value, $unit = null)
	{
	    if(is_int($value))
	    {
	        return $value;
	    }

	    if(is_numeric($value) && is_string($value) && $unit === null)
	    {
	        $unit = self::UNIT_PDF;
	    }
	    else
	    {
            $unit = $unit ? : strtolower(substr($value, -2, 2));
	    }
	    
	    return (int) round($this->doConvertUnit($value, $unit));		
	}
    
	protected function convertInUnit($value)
	{
		return $value * $this->dpi;		
	}

	protected function convertPdfUnit($value)
	{
		return $value * $this->pixelPerUnits;		
	}

	protected function convertPtUnit($value)
	{
		return $value * $this->dpi / 72;		
	}

	protected function convertPxUnit($value)
	{
		return (int) $value;		
	}
}