<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Configuration;

use PHPPdf\Cache\Cache;

/**
 * Configuration loader
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface Loader
{
    /**
     * @return PHPPdf\Node\Factory
     */
    public function createNodeFactory();
    
    /**
     * @return PHPPdf\Enhancement\Factory
     */
    public function createEnhancementFactory();
    
    /**
     * @return PHPPdf\Font\Registry
     */
    public function createFontRegistry();
    
    /**
     * @return UnitConverter
     */
    public function createUnitConverter();
    
    public function setCache(Cache $cache);
}