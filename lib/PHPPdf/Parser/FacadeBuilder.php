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
    private $useCacheForStylesheetConstraint = false;

    private function __construct(FacadeConfiguration $configuration = null)
    {
        if($configuration === null)
        {
            $configuration = new FacadeConfiguration();
        }

        $this->setFacadeConfiguration($configuration);
    }

    /**
     * Static constructor
     * 
     * @return FacadeBuilder
     */
    public static function create(FacadeConfiguration $configuration = null)
    {
        return new self($configuration);
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
        $facade->setUseCacheForStylesheetConstraint($this->useCacheForStylesheetConstraint);

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

    /**
     * Switch on/off cache for stylesheet.
     *
     * If you switch on cache for stylesheet constraints,
     * you should set cache parameters by method setCache(), otherwise NullCache as default will
     * be used.
     *
     * @see setCache()
     * @param boolean $useCache Cache for Stylesheets should by used?
     * @return FacadeBuilder
     */
    public function setUseStylesheetConstraintCache($useCache)
    {
        $this->useCacheForStylesheetConstraint = (bool) $useCache;

        return $this;
    }
}