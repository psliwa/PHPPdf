<?php

namespace PHPPdf\Configuration;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
use PHPPdf\Cache\Cache;

interface Loader
{
    /**
     * @return PHPPdf\Glyph\Factory
     */
    public function createGlyphFactory();
    
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