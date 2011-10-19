<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Parser;

use PHPPdf\Core\Point;

use PHPPdf\Configuration\Loader;
use PHPPdf\Node\TextTransformator;
use PHPPdf\Document,
    PHPPdf\Parser\StylesheetParser,
    PHPPdf\Parser\ComplexAttributeFactoryParser,
    PHPPdf\Parser\FontRegistryParser,
    PHPPdf\Cache\Cache,
    PHPPdf\Cache\NullCache,
    PHPPdf\Core\DataSource,
    PHPPdf\Parser\NodeFactoryParser;

/**
 * Simple facade whom encapsulate logical complexity of this library
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Facade
{
    private $documentParser;
    private $stylesheetParser;
    private $document;
    private $cache;
    private $loaded = false;
    private $useCacheForStylesheetConstraint = false;
    private $configurationLoader;

    public function __construct(Loader $configurationLoader, DocumentParser $documentParser, StylesheetParser $stylesheetParser)
    {
        $this->configurationLoader = $configurationLoader;
        
        $unitConverter = $this->configurationLoader->createUnitConverter();

        $this->setCache(NullCache::getInstance());
        $document = new Document($unitConverter);
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

    /**
     * Returns pdf document object
     * 
     * @return PHPPdf\Document
     */
    public function getDocument()
    {
        return $this->document;
    }

    public function setDocument(Document $document)
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
     * @return string Content of pdf document
     */
    public function render($documentContent, $stylesheetContent = null)
    {
        $complexAttributeFactory = $this->configurationLoader->createComplexAttributeFactory();
        
        $unitConverter = $this->configurationLoader->createUnitConverter();
        if($unitConverter)
        {
            $this->getDocument()->setUnitConverter($unitConverter);
        }
        
        $this->getDocument()->setComplexAttributeFactory($complexAttributeFactory);
        $fontDefinitions = $this->configurationLoader->createFontRegistry();
        $this->getDocument()->addFontDefinitions($fontDefinitions);
        $this->getDocumentParser()->setComplexAttributeFactory($complexAttributeFactory);
        $this->getDocumentParser()->setNodeFactory($this->configurationLoader->createNodeFactory());

        $stylesheetConstraint = $this->retrieveStylesheetConstraint($stylesheetContent);

        $relativePathToResources = str_replace('\\', '/', realpath(__DIR__.'/../Resources'));
        $documentContent = str_replace('%resources%', $relativePathToResources, $documentContent);

        $pageCollection = $this->getDocumentParser()->parse($documentContent, $stylesheetConstraint);
        $this->updateStylesheetConstraintCacheIfNecessary($stylesheetConstraint);
        unset($stylesheetConstraint);

        return $this->doRender($pageCollection);
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

    public function retrieveStylesheetConstraint($stylesheetXml)
    {
       $stylesheetConstraint = null;

        if($stylesheetXml)
        {
            if(!$stylesheetXml instanceof DataSource)
            {
                $stylesheetXml = DataSource::fromString($stylesheetXml);
            }

            if(!$this->useCacheForStylesheetConstraint)
            {
                $stylesheetConstraint = $this->parseStylesheet($stylesheetXml);
            }
            else
            {
                $stylesheetConstraint = $this->loadStylesheetConstraintFromCache($stylesheetXml);
            }
        }

        return $stylesheetConstraint;
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