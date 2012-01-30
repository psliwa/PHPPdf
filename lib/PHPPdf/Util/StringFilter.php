<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Util;

/**
 * String filter.
 * 
 * Modifies and filters string variable
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface StringFilter
{
    public function filter($value); 
}