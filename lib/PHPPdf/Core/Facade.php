<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core;

use PHPPdf\Util\AbstractStringFilterContainer;
use PHPPdf\Util\StringFilter;
use PHPPdf\Exception\InvalidArgumentException;
use PHPPdf\Core\Parser\ColorPaletteParser;
use PHPPdf\Parser\Parser;
use PHPPdf\Core\Parser\StylesheetConstraint;
use PHPPdf\Core\Parser\CachingStylesheetConstraint;
use PHPPdf\Core\Parser\DocumentParser;
use PHPPdf\Core\Configuration\Loader;
use PHPPdf\Core\Node\TextTransformator;
use PHPPdf\Core\Parser\StylesheetParser;
use PHPPdf\Core\Parser\ComplexAttributeFactoryParser;
use PHPPdf\Core\Parser\FontRegistryParser;
use PHPPdf\Cache\Cache;
use PHPPdf\Cache\NullCache;
use PHPPdf\DataSource\DataSource;
use PHPPdf\Core\Parser\NodeFactoryParser;

/**
 * Simple facade whom encapsulate logical complexity of this library
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Facade extends AbstractStringFilterContainer
{
    private $documentParser;
    private $stylesheetParser;
    private $document;
    private $cache;
    private $loaded = false;
    private $useCacheForStylesheetConstraint = false;
    private $configurationLoader;
    private $colorPaletteParser;
    private $engineType = 'pdf';

    public function __construct(Loader $configurationLoader, Document $document, DocumentParser $documentParser, StylesheetParser $stylesheetParser)
    {
        $this->configurationLoader = $configurationLoader;
        $this->configurationLoader->setUnitConverter($document);
        
        $this->setCache(NullCache::getInstance());
        $documentParser->setDocument($document);
        $nodeManager = $documentParser->getNodeManager();
        if($nodeManager)
        {
            $documentParser->addListener($nodeManager);
        }
        $this->setDocumentParser($documentParser);
        $this->setStylesheetParser($stylesheetParser);
        $this->setDocument($document);
    }
    
    public function setEngineType($engineType)
	{
		$this->engineType = $engineType;
	}

	public function setCache(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @return DocumentParser
     */
    public function getDocumentParser()
    {
        return $this->documentParser;
    }

    public function getStylesheetParser()
    {
        return $this->stylesheetParser;
    }

    public function setDocumentParser(DocumentParser $documentParser)
    {
        $this->documentParser = $documentParser;
    }

    public function setStylesheetParser(StylesheetParser $stylesheetParser)
    {
        $this->stylesheetParser = $stylesheetParser;
    }
    
    public function setColorPaletteParser(Parser $colorPaletteParser)
	{
		$this->colorPaletteParser = $colorPaletteParser;
	}
	
	protected function getColorPaletteParser()
	{
	    if(!$this->colorPaletteParser)
	    {
	        $this->colorPaletteParser = new ColorPaletteParser();
	    }
	    
	    return $this->colorPaletteParser;
	}

	/**
     * Returns pdf document object
     * 
     * @return PHPPdf\Core\Document
     */
    public function getDocument()
    {
        return $this->document;
    }

    private function setDocument(Document $document)
    {
        $this->document = $document;
    }

    private function setFacadeConfiguration(FacadeConfiguration $facadeConfiguration)
    {
        $this->facadeConfiguration = $facadeConfiguration;
    }

    /**
     * @param boolean $useCache Stylsheet constraints should be cached?
     */
    public function setUseCacheForStylesheetConstraint($useCache)
    {
        $this->useCacheForStylesheetConstraint = (bool) $useCache;
    }

    /**
     * Convert text document to pdf document
     * 
     * @param string|DataSource $documentContent Source document content
     * @param DataSource[]|string[]|DataSource|string $stylesheetContents Stylesheet source(s)
     * @param string|DataSource $colorPaletteContent Palette of colors source
     * 
     * @return string Content of pdf document
     * 
     * @throws PHPPdf\Exception\Exception
     */
    public function render($documentContent, $stylesheetContents = array(), $colorPaletteContent = null)
    {
        $colorPalette = new ColorPalette((array) $this->configurationLoader->createColorPalette());
        
        if($colorPaletteContent)
        {
            $colorPalette->merge($this->parseColorPalette($colorPaletteContent));
        }
        
        $this->document->setColorPalette($colorPalette);
        
        $complexAttributeFactory = $this->configurationLoader->createComplexAttributeFactory();
        
        $this->getDocument()->setComplexAttributeFactory($complexAttributeFactory);
        $fontDefinitions = $this->configurationLoader->createFontRegistry($this->engineType);
        $this->getDocument()->addFontDefinitions($fontDefinitions);
        $this->getDocumentParser()->setComplexAttributeFactory($complexAttributeFactory);
        $this->getDocumentParser()->setNodeFactory($this->configurationLoader->createNodeFactory());

        $stylesheetConstraint = $this->retrieveStylesheetConstraint($stylesheetContents);

        foreach($this->stringFilters as $filter)
        {
            $documentContent = $filter->filter($documentContent);
        }

        $pageCollection = $this->getDocumentParser()->parse($documentContent, $stylesheetConstraint);
        $this->updateStylesheetConstraintCacheIfNecessary($stylesheetConstraint);
        unset($stylesheetConstraint);

        return $this->doRender($pageCollection);
    }
    
    private function parseColorPalette($colorPaletteContent)
    {        
        if(!$colorPaletteContent instanceof DataSource)
        {
            $colorPaletteContent = DataSource::fromString($colorPaletteContent);
        }
        
        $id = $colorPaletteContent->getId();
        
        if($this->cache->test($id))
        {
            $colors = (array) $this->cache->load($id);
        }
        else
        {
            $colors = (array) $this->getColorPaletteParser()->parse($colorPaletteContent->read());
            $this->cache->save($colors, $id);
        }

        return $colors;
    }

    private function doRender($pageCollection)
    {
        $this->getDocument()->draw($pageCollection);
        $pageCollection->flush();
        unset($pageCollection);
        
        $content = $this->getDocument()->render();
        $this->getDocument()->initialize();

        return $content;
    }

    public function retrieveStylesheetConstraint($stylesheetContents)
    {
        if($stylesheetContents === null)
        {
            return null;
        }
        elseif(is_string($stylesheetContents))
        {
            $stylesheetContents = array(DataSource::fromString($stylesheetContents));
        }
        elseif($stylesheetContents instanceof DataSource)
        {
            $stylesheetContents = array($stylesheetContents);
        }
        elseif(!is_array($stylesheetContents))
        {
            throw new InvalidArgumentException('$stylesheetContents must be an array, null or DataSource object.');
        }
        
        $constraints = array();
        
        foreach($stylesheetContents as $stylesheetContent)
        {
            if(!$stylesheetContent instanceof DataSource)
            {
                $stylesheetContent = DataSource::fromString($stylesheetContent);
            }

            if(!$this->useCacheForStylesheetConstraint)
            {
                $constraints[] = $this->parseStylesheet($stylesheetContent);
            }
            else
            {
                $constraints[] = $this->loadStylesheetConstraintFromCache($stylesheetContent);
            }
        }
        
        if(!$constraints)
        {
            return null;
        }
        elseif(count($constraints) === 1)
        {
            return current($constraints);
        }

        return $constraints[0]->merge($constraints);
    }

    /**
     * @return StylesheetConstraint
     */
    private function parseStylesheet(DataSource $ds)
    {
        return $this->getStylesheetParser()->parse($ds->read());
    }

    private function loadStylesheetConstraintFromCache(DataSource $ds)
    {
        $id = $ds->getId();
        if($this->cache->test($id))
        {
            $stylesheetConstraint = $this->cache->load($id);
        }
        else
        {
            $csc = new CachingStylesheetConstraint();
            $csc->setCacheId($id);
            $this->getStylesheetParser()->setRoot($csc);
            
            $stylesheetConstraint = $this->parseStylesheet($ds);
            $this->cache->save($stylesheetConstraint, $id);
        }

        return $stylesheetConstraint;
    }

    private function updateStylesheetConstraintCacheIfNecessary(StylesheetConstraint $constraint = null)
    {
        if($constraint && $this->useCacheForStylesheetConstraint && $constraint->isResultMapModified())
        {
            $this->cache->save($constraint, $constraint->getCacheId());
        }
    }
}