<?php

namespace PHPPdf\Configuration;

use PHPPdf\Parser\FontRegistryParser,
    PHPPdf\Parser\EnhancementFactoryParser,
    PHPPdf\Parser\GlyphFactoryParser,
    PHPPdf\Cache\NullCache,
    PHPPdf\Cache\Cache;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class LoaderImpl implements Loader
{
    private $glyphFile = null;
    private $enhancementFile = null;
    private $fontFile = null;
    
    private $enhancementFactory;
    private $glyphFactory;
    private $fontRegistry;
    
    private $cache;
    
    public function __construct($glyphFile = null, $enhancementFile = null, $fontFile = null)
    {
        if($glyphFile === null)
        {
            $glyphFile = __DIR__.'/../Resources/config/glyphs.xml';
        }
        
        if($enhancementFile === null)
        {
            $enhancementFile = __DIR__.'/../Resources/config/enhancements.xml';
        }
        
        if($fontFile === null)
        {
            $fontFile = __DIR__.'/../Resources/config/fonts.xml';
        }
        
        $this->glyphFile = $glyphFile;        
        $this->enhancementFile = $enhancementFile;        
        $this->fontFile = $fontFile;   

        $this->setCache(NullCache::getInstance());
    }

    public function setCache(Cache $cache)
    {
        $this->cache = $cache;
    }

	public function createEnhancementFactory()
    {
        if($this->enhancementFactory === null)
        {
            $this->loadEnhancements();
        }        

        return $this->enhancementFactory;
    }

	public function createFontRegistry()
    {
        if($this->fontRegistry === null)
        {
            $this->loadFonts();
        }        

        return $this->fontRegistry;
        
    }

	public function createGlyphFactory()
    {
        if($this->glyphFactory === null)
        {
            $this->loadGlyphs();
        }        

        return $this->glyphFactory;
    }

    private function loadGlyphs()
    {
        $file = $this->glyphFile;

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
        
        $this->glyphFactory = $glyphFactory;
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
        $file = $this->enhancementFile;

        $doLoadEnhancements = function($content)
        {
            $enhancementFactoryParser = new EnhancementFactoryParser();
            $enhancementFactory = $enhancementFactoryParser->parse($content);

            return $enhancementFactory;
        };

        $this->enhancementFactory = $this->getFromCacheOrCallClosure($file, $doLoadEnhancements);
    }

    private function loadFonts()
    {
        $file = $this->fontFile;

        $doLoadFonts = function($content)
        {
            $fontRegistryParser = new FontRegistryParser();
            $fontRegistry = $fontRegistryParser->parse($content);

            return $fontRegistry;
        };

        $this->fontRegistry = $this->getFromCacheOrCallClosure($file, $doLoadFonts);
    }   
}