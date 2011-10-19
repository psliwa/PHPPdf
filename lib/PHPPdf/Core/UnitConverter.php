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
interface UnitConverter
{
    const UNIT_PIXEL = 'px';
    const UNIT_CENTIMETER = 'cm';
    const UNIT_MILIMETER = 'mm';
    const UNIT_INCH = 'in';
    const UNIT_POINT = 'pt';
    const UNIT_PICA = 'pc';
    const UNIT_EM = 'em';
    const UNIT_EX = 'ex';
    
    public function convertUnit($value, $unit = null);
    public function convertPercentageValue($percent, $value);
}