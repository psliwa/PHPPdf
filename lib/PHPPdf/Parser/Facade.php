<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Parser;

use PHPPdf\Configuration\Loader;

use PHPPdf\Glyph\TextTransformator;

use PHPPdf\Parser\DocumentParser,
    PHPPdf\Document,
    PHPPdf\Parser\StylesheetParser,
    PHPPdf\Parser\EnhancementFactoryParser,
    PHPPdf\Parser\FontRegistryParser,
    PHPPdf\Cache\Cache,
    PHPPdf\Cache\NullCache,
    PHPPdf\Util\DataSource,
    PHPPdf\Parser\GlyphFactoryParser;

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

    public function __construct(Loader $configurationLoader)
    {
        $this->configurationLoader = $configurationLoader;

        $this->setCache(NullCache::getInstance());
        $this->setDocumentParser(new DocumentParser());
        $this->setStylesheetParser(new StylesheetParser());
        $this->setDocument(new Document());
    }

    public function setCache(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     *
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

    public function setUseCacheForStylesheetConstraint($useCache)
    {
        $this->useCacheForStylesheetConstraint = (bool) $useCache;
    }

    public function render($documentXml, $stylesheetXml = null)
    {
        $enhancementFactory = $this->configurationLoader->createEnhancementFactory();
        
        $this->getDocument()->setEnhancementFactory($enhancementFactory);
        $fontDefinitions = $this->configurationLoader->createFontRegistry();
        $this->getDocument()->addFontDefinitions($fontDefinitions);
        $this->getDocumentParser()->setEnhancementFactory($enhancementFactory);
        $this->getDocumentParser()->setGlyphFactory($this->configurationLoader->createGlyphFactory());

        $stylesheetConstraint = $this->retrieveStylesheetConstraint($stylesheetXml);

        $relativePathToResources = str_replace('\\', '/', realpath(__DIR__.'/../Resources'));
        $documentXml = str_replace('%resources%', $relativePathToResources, $documentXml);
        $pageCollection = $this->getDocumentParser()->parse($documentXml, $stylesheetConstraint);
        $this->getDocument()->draw($pageCollection);

        $content = $this->getDocument()->render();
        $this->getDocument()->initialize();

        $this->updateStylesheetConstraintCacheIfNecessary($stylesheetConstraint);

        return $content;
    }

    private function retrieveStylesheetConstraint($stylesheetXml)
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