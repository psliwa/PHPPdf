<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Engine;

/**
 * Image
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface Image
{
    /**
     * @return float Image width in default document's unit
     */
    public function getOriginalWidth();
    
    /**
     * @return float Image height in default document's unit
     */
    public function getOriginalHeight();
}