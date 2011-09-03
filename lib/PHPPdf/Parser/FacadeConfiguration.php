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
            'node' => __DIR__.'/../Resources/config/nodes.xml',
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
     * Set config file for populating node factory
     *
     * @param string $file
     * @return FacadeConfiguration
     */
    public function setNodesConfigFile($file)
    {
        $this->configFiles['node'] = $file;

        return $this;
    }

    public function getNodesConfigFile()
    {
        return $this->configFiles['node'];
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