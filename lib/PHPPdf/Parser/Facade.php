<?php

namespace PHPPdf\Parser;

use PHPPdf\Parser\DocumentParser,
    PHPPdf\Document,
    PHPPdf\Parser\StylesheetParser,
    PHPPdf\Parser\EnhancementFactoryParser,
    PHPPdf\Parser\FontRegistryParser,
    PHPPdf\Cache\Cache,
    PHPPdf\Cache\NullCache,
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

    public function render($documentXml, $stylesheetXml = null)
    {
        $facadeConfiguration = $this->facadeConfiguration;
        
        $this->load();

        $stylesheetConstraint = null;

        if($stylesheetXml)
        {
            $stylesheetConstraint = $this->getStylesheetParser()->parse($stylesheetXml);
        }

        $pageCollection = $this->getDocumentParser()->parse($documentXml, $stylesheetConstraint);
        $this->getDocument()->draw($pageCollection);

        $content = $this->getDocument()->render();
        $this->getDocument()->initialize();

        return $content;
    }


    private function load()
    {
        if(!$this->loaded)
        {
            $this->loadGlyphs();
            $this->loadEnhancements();
            $this->loadFonts();
            $this->loadFormatters();

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

        $glyphFactory = $this->getFromCacheOrCallClosure($file, $doLoadGlyphs);
        
        if($glyphFactory->hasPrototype('page'))
        {
            $glyphFactory->addPrototype('dynamic-page', new \PHPPdf\Glyph\DynamicPage($glyphFactory->create('page')));
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
            $fonts = $fontRegistryParser->parse($content);

            return $fonts;
        };

        $fonts = $this->getFromCacheOrCallClosure($file, $doLoadFonts);

        foreach($fonts as $name => $font)
        {
            $this->getDocument()->getFontRegistry()->register($name, $font);
        }
    }

    private function loadFormatters()
    {
        $file = $this->facadeConfiguration->getFormattersConfigFile();

        $doLoadFormatters = function($content)
        {
            $parser = new FormatterParser();
            $formatters = $parser->parse($content);

            return $formatters;
        };

        $formatters = $this->getFromCacheOrCallClosure($file, $doLoadFormatters);

        foreach($formatters as $formatter)
        {
            $this->getDocument()->addFormatter($formatter);
        }
    }
}