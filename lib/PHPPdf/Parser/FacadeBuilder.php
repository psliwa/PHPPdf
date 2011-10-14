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
    const PARSER_XML = 'xml';
    const PARSER_MARKDOWN = 'markdown';
    
    private $configurationLoader = null;
    private $cacheType = null;
    private $cacheOptions = null;
    private $useCacheForStylesheetConstraint = true;
    private $useCacheForConfigurationLoader = true;
    private $documentParserType = self::PARSER_XML;
    private $markdownStylesheetFilepath = null;
    private $markdownDocumentTemplateFilepath = null;

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

    public function setConfigurationLoader(Loader $configurationLoader)
    {
        $this->configurationLoader = $configurationLoader;
        
        return $this;
    }
    
    public function setUseCacheForConfigurationLoader($flag)
    {
        $this->useCacheForConfigurationLoader = (bool) $flag;
        
        return $this;
    }

    /**
     * Create Facade object
     *
     * @return Facade
     */
    public function build()
    {
        $documentParser = $this->createDocumentParser();
        $facade = new Facade($this->configurationLoader, $documentParser);
        
        if($documentParser instanceof FacadeAware)
        {
            $documentParser->setFacade($facade);
        }
        
        $facade->setUseCacheForStylesheetConstraint($this->useCacheForStylesheetConstraint);

        if($this->cacheType && $this->cacheType !== 'Null')
        {
            $cache = new CacheImpl($this->cacheType, $this->cacheOptions);
            $facade->setCache($cache);
            
            if($this->useCacheForConfigurationLoader)
            {
                $this->configurationLoader->setCache($cache);
            }
        }

        return $facade;
    }
    
    /**
     * @return DocumentParser
     */
    private function createDocumentParser()
    {
        $parser = new XmlDocumentParser();
        
        if($this->documentParserType === self::PARSER_MARKDOWN)
        {
            $parser = new MarkdownDocumentParser($parser);
            $parser->setStylesheetFilepath($this->markdownStylesheetFilepath);
            $parser->setDocumentTemplateFilepath($this->markdownDocumentTemplateFilepath);
        }
        
        return $parser;
    }

    /**
     * Set cache type and options for facade
     *
     * @param string $type Type of cache, see {@link PHPPdf\Cache\CacheImpl} engine constants
     * @param array $options Options for cache
     * 
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
     * 
     * @return FacadeBuilder
     */
    public function setUseCacheForStylesheetConstraint($useCache)
    {
        $this->useCacheForStylesheetConstraint = (bool) $useCache;

        return $this;
    }
    
    /**
     * @return FacadeBuilder
     */
    public function setDocumentParserType($type)
    {
        $this->documentParserType = $type;
        
        return $this;
    }
    
    /**
     * Sets stylesheet filepath for markdown document parser
     * 
     * @param string|null $filepath Filepath
     * 
     * @return FacadeBuilder
     */
    public function setMarkdownStylesheetFilepath($filepath)
    {
        $this->markdownStylesheetFilepath = $filepath;
        
        return $this;
    }
    
    /**
     * Sets document template for markdown document parser
     * 
     * @param string|null $filepath Filepath to document template
     * 
     * @return FacadeBuilder
     */
    public function setMarkdownDocumentTemplateFilepath($filepath)
    {
        $this->markdownDocumentTemplateFilepath = $filepath;
        
        return $this;
    }
}