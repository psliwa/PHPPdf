<?php

namespace PHPPdf\Parser;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class FacadeParameters
{
    private $configFiles = array();

    public function __construct()
    {
        $this->configFiles = array(
            'glyph' => __DIR__.'/../Resources/config/glyphs.xml',
            'enhancement' => __DIR__.'/../Resources/config/enhancements.xml',
            'font' => __DIR__.'/../Resources/config/fonts.xml',
            'formatter' => __DIR__.'/../Resources/config/formatters.xml',
        );
    }

    /**
     * Static constructor
     * 
     * @return FacadeParameters
     */
    public static function newInstance()
    {
        return new self();
    }

    /**
     * Set config file for populating glyph factory
     *
     * @param string $file
     * @return FacadeParameters
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
     * @return FacadeParameters
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
     * Set config file for populating pdf's formatters
     *
     * @param string $file
     * @return FacadeParameters
     */
    public function setFormattersConfigFile($file)
    {
        $this->configFiles['formatter'] = $file;

        return $this;
    }

    public function getFormattersConfigFile()
    {
        return $this->configFiles['formatter'];
    }
    
    /**
     * Set config file for populating font registry
     *
     * @param string $file
     * @return FacadeParameters
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