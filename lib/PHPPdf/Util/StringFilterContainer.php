<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Util;

/**
 * String filter container
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface StringFilterContainer
{
    public function setStringFilters(array $filters);
}