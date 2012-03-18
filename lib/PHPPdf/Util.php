<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf;

use PHPPdf\Exception\BadMethodCallException;

/**
 * Class with generic util functions
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
final class Util
{
    private function __construct()
    {
        throw new BadMethodCallException(sprintf('Object of "%s" class can not be created.', __CLASS__));
    }
    
    public static function convertBooleanValue($value)
    {
        $knownValues = array('true' => true, 'false' => false, 1 => true, 0 => false, '1' => true, '0' => false, 'yes' => true, 'no' => false);

        return isset($knownValues[$value]) ? $knownValues[$value] : (boolean) $value;
    }
    
    /**
     * Converts angle value to radians. 
     * 
     * When value is "deg" suffixed, it means value is in degrees.
     * 
     * @return float|null angle in radians or null
     */
    public static function convertAngleValue($value)
    {
        if($value !== null && strpos($value, 'deg') !== false)
        {
            $value = (float) $value;
            $value = deg2rad($value);
        }
        
        return $value !== null ? ((float) $value) : null;
    }
    
    public static function calculateDependantSizes($width, $height, $ratio)
    {
        if(!$width && $height)
        {
            $width = $ratio * $height;
        }
    
        if(!$height && $width)
        {
            $height = 1/$ratio * $width;
        }

        return array($width, $height);
    }
}