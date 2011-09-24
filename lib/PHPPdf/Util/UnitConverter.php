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
interface UnitConverter
{
    public function convertUnit($value);
    public function convertPercentageValue($percent, $value);
}