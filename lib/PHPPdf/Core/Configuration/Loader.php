<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Configuration;

use PHPPdf\Cache\Cache;

/**
 * Configuration loader
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface Loader
{
    /**
     * @return PHPPdf\Core\Node\Factory
     */
    public function createNodeFactory();
    
    /**
     * @return ComplexAttributeFactory
     */
    public function createComplexAttributeFactory();
    
    /**
     * @return FontRegistry
     */
    public function createFontRegistry();
    
    /**
     * @return UnitConverter
     */
    public function createUnitConverter();
    
    public function setCache(Cache $cache);
}