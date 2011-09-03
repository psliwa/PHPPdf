<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Configuration;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
use PHPPdf\Cache\Cache;

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
    
    public function setCache(Cache $cache);
}