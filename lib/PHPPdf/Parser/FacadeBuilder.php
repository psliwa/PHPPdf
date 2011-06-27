<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Parser;

use PHPPdf\Configuration\LoaderImpl;

use PHPPdf\Configuration\Loader;

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
    private $configurationLoader = null;
    private $cacheType = null;
    private $cacheOptions = null;
    private $useCacheForStylesheetConstraint = false;

    private function __construct(Loader $configurationLoader = null)
    {
        if($configurationLoader === null)
        {
            $configurationLoader = new LoaderImpl();
        }

        $this->setConfigurationLoader($configurationLoader);
    }

    /**
     * Static constructor
     * 
     * @return FacadeBuilder
     */
    public static function create(Loader $configuration = null)
    {
        return new self($configuration);
    }

    private function setConfigurationLoader(Loader $configurationLoader)
    {
        $this->configurationLoader = $configurationLoader;
    }

    /**
     * Create Facade object
     *
     * @return Facade
     */
    public function build()
    {
        $facade = new Facade($this->configurationLoader);
        $facade->setUseCacheForStylesheetConstraint($this->useCacheForStylesheetConstraint);

        if($this->cacheType && $this->cacheType !== 'Null')
        {
            $cache = new CacheImpl($this->cacheType, $this->cacheOptions);
            $facade->setCache($cache);
            $this->configurationLoader->setCache($cache);
        }

        return $facade;
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
    public function setUseCacheForStylesheetConstraint($useCache)
    {
        $this->useCacheForStylesheetConstraint = (bool) $useCache;

        return $this;
    }
}