<?php

namespace PHPPdf\Parser;

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
    private $facadeConfiguration;
    private $loaded = false;
    private $useCacheForStylesheetConstraint = false;

    public function __construct(FacadeConfiguration $facadeConfiguration = null)
    {
        if($facadeConfiguration === null)
        {
            $facadeConfiguration = FacadeConfiguration::newInstance();
        }

        $this->setCache(NullCache::getInstance());
        $this->setDocumentParser(new DocumentParser());
        $this->setStylesheetParser(new StylesheetParser());
        $this->setDocument(new Document());
        $this->setFacadeConfiguration($facadeConfiguration);
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
        $facadeConfiguration = $this->facadeConfiguration;
        
        $this->load();

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

    private function load()
    {
        if(!$this->loaded)
        {
            $this->loadGlyphs();
            $this->loadEnhancements();
            $this->loadFonts();

            $this->loaded = true;
        }
    }

    private function loadGlyphs()
    {
        $file = $this->facadeConfiguration->getGlyphsConfigFile();

        $doLoadGlyphs = function($content)
        {
            $glyphFactoryParser = new GlyphFactoryParser();

            $glyphFactory = $glyphFactoryParser->parse($content);

            return $glyphFactory;
        };

        /* @var $glyphFactory PHPpdf\Glyph\Factory */
        $glyphFactory = $this->getFromCacheOrCallClosure($file, $doLoadGlyphs);

        //TODO: DI
        if($glyphFactory->hasPrototype('page') && $glyphFactory->hasPrototype('dynamic-page'))
        {
            $page = $glyphFactory->create('page');
            $glyphFactory->getPrototype('dynamic-page')->setPrototypePage($page);
        }

        $this->getDocumentParser()->setGlyphFactory($glyphFactory);
    }

    private function getFromCacheOrCallClosure($file, \Closure $closure)
    {
        $id = $this->getCacheId($file);

        if($this->cache->test($id))
        {
            $result = $this->cache->load($id);
        }
        else
        {
            $content = $this->loadFile($file);
            $result = $closure($content);
            $this->cache->save($result, $id);
        }

        return $result;
    }

    private function getCacheId($file)
    {
        return str_replace('-', '_', (string) crc32($file));
    }

    private function loadFile($file)
    {
        return \PHPPdf\Util\DataSource::fromFile($file)->read();
    }

    private function loadEnhancements()
    {
        $file = $this->facadeConfiguration->getEnhancementsConfigFile();

        $doLoadEnhancements = function($content)
        {
            $enhancementFactoryParser = new EnhancementFactoryParser();
            $enhancementFactory = $enhancementFactoryParser->parse($content);

            return $enhancementFactory;
        };

        $enhancementFactory = $this->getFromCacheOrCallClosure($file, $doLoadEnhancements);

        $this->getDocument()->setEnhancementFactory($enhancementFactory);
        $this->getDocumentParser()->setEnhancementFactory($enhancementFactory);
    }

    private function loadFonts()
    {
        $file = $this->facadeConfiguration->getFontsConfigFile();

        $doLoadFonts = function($content)
        {
            $fontRegistryParser = new FontRegistryParser();
            $fontRegistry = $fontRegistryParser->parse($content);

            return $fontRegistry;
        };

        $fontRegistry = $this->getFromCacheOrCallClosure($file, $doLoadFonts);
        $this->getDocument()->setFontRegistry($fontRegistry);
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