<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf;

/**
 * Class with generic util functions
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
final class Util
{
    private function __construct()
    {
        throw new \BadMethodCallException(sprintf('Object of "%s" class can not be created.', __CLASS__));
    }
    
    public static function convertFromPercentageValue($percent, $value)
    {
        if(strpos($percent, '%') !== false)
        {
            $percent = (double) $percent;
            $percent = $value*$percent / 100;
        }
        return $percent;
    }    
    
    public static function convertBooleanValue($value)
    {
        $knownValues = array('true' => true, 'false' => false, 1 => true, 0 => false, '1' => true, '0' => false, 'yes' => true, 'no' => false);

        return isset($knownValues[$value]) ? $knownValues[$value] : (boolean) $value;
    }
}