<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Engine;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface Image
{
    public function getOriginalWidth();
    
    public function getOriginalHeight();
}