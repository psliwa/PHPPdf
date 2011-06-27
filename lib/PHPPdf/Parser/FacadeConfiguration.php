<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Parser;

/**
 * Configuration for Facade. Contains informations about config files.
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class FacadeConfiguration
{
    private $configFiles = array();

    public function __construct()
    {
        $this->configFiles = array(
            'glyph' => __DIR__.'/../Resources/config/glyphs.xml',
            'enhancement' => __DIR__.'/../Resources/config/enhancements.xml',
            'font' => __DIR__.'/../Resources/config/fonts.xml',
        );
    }

    /**
     * Static constructor
     * 
     * @return FacadeConfiguration
     */
    public static function newInstance()
    {
        return new self();
    }

    /**
     * Set config file for populating glyph factory
     *
     * @param string $file
     * @return FacadeConfiguration
     */
    public function setGlyphsConfigFile($file)
    {
        $this->configFiles['glyph'] = $file;

        return $this;
    }

    public function getGlyphsConfigFile()
    {
        return $this->configFiles['glyph'];
    }

    /**
     * Set config file for populating enhancement factory
     *
     * @param string $file
     * @return FacadeConfiguration
     */
    public function setEnhancementsConfigFile($file)
    {
        $this->configFiles['enhancement'] = $file;

        return $this;
    }

    public function getEnhancementsConfigFile()
    {
        return $this->configFiles['enhancement'];
    }
    
    /**
     * Set config file for populating font registry
     *
     * @param string $file
     * @return FacadeConfiguration
     */
    public function setFontsConfigFile($file)
    {
        $this->configFiles['font'] = $file;

        return $this;
    }

    public function getFontsConfigFile()
    {
        return $this->configFiles['font'];
    }
}