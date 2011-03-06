<?php

namespace PHPPdf\Parser;

use PHPPdf\Cache\CacheImpl;

/**
 * Facade builder.
 *
 * Object of this class is able to configure and build specyfic Facade object.
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class FacadeBuilder
{
    private $configuration = null;
    private $cacheType = null;
    private $cacheOptions = null;

    public function __construct(FacadeConfiguration $configuration = null)
    {
        if($configuration === null)
        {
            $configuration = new FacadeConfiguration();
        }

        $this->setFacadeConfiguration($configuration);
    }

    private function setFacadeConfiguration(FacadeConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Create Facade object
     *
     * @return Facade
     */
    public function build()
    {
        $facade = new Facade($this->configuration);

        if($this->cacheType && $this->cacheType !== 'Null')
        {
            $cache = new CacheImpl($this->cacheType, $this->cacheOptions);
            $facade->setCache($cache);
        }

        return $facade;
    }

    /**
     * @see PHPPdf\Parser\FacadeConfiguration::setGlyphsConfigFile()
     *
     * @param string $file
     * @return FacadeBuilder
     */
    public function setGlyphsConfigFile($file)
    {
        $this->configuration->setGlyphsConfigFile($file);

        return $this;
    }

    /**
     * @see PHPPdf\Parser\FacadeConfiguration::setEnhancementsConfigFile()
     *
     * @param string $file
     * @return FacadeBuilder
     */
    public function setEnhancementsConfigFile($file)
    {
        $this->configuration->setEnhancementsConfigFile($file);

        return $this;
    }

    /**
     * @see PHPPdf\Parser\FacadeConfiguration::setFormattersConfigFile()
     *
     * @param string $file
     * @return FacadeBuilder
     */
    public function setFormattersConfigFile($file)
    {
        $this->configuration->setFormattersConfigFile($file);

        return $this;
    }

    /**
     * @see PHPPdf\Parser\FacadeConfiguration::setFontsConfigFile()
     *
     * @param string $file
     * @return FacadeBuilder
     */
    public function setFontsConfigFile($file)
    {
        $this->configuration->setFontsConfigFile($file);

        return $this;
    }

    /**
     * Set cache type and options for facade
     *
     * @param string $type Type of cache, see {@link PHPPdf\Cache\CacheImpl} engine constants
     * @param array $options Options for cache
     * @return FacadeBuilder
     */
    public function setCache($type, array $options = array())
    {
        $this->cacheType = $type;
        $this->cacheOptions = $options;

        return $this;
    }
}