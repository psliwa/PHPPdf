<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Configuration;

use PHPPdf\Parser\FontRegistryParser,
    PHPPdf\Parser\EnhancementFactoryParser,
    PHPPdf\Parser\NodeFactoryParser,
    PHPPdf\Cache\NullCache,
    PHPPdf\Cache\Cache;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class LoaderImpl implements Loader
{
    private $nodeFile = null;
    private $enhancementFile = null;
    private $fontFile = null;
    
    private $enhancementFactory;
    private $nodeFactory;
    private $fontRegistry;
    
    private $cache;
    
    public function __construct($nodeFile = null, $enhancementFile = null, $fontFile = null)
    {
        if($nodeFile === null)
        {
            $nodeFile = __DIR__.'/../Resources/config/nodes.xml';
        }
        
        if($enhancementFile === null)
        {
            $enhancementFile = __DIR__.'/../Resources/config/enhancements.xml';
        }
        
        if($fontFile === null)
        {
            $fontFile = __DIR__.'/../Resources/config/fonts.xml';
        }
        
        $this->nodeFile = $nodeFile;        
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
            $this->enhancementFactory = $this->loadEnhancements();
        }        

        return $this->enhancementFactory;
    }

	public function createFontRegistry()
    {
        if($this->fontRegistry === null)
        {
            $this->fontRegistry = $this->loadFonts();
        }        

        return $this->fontRegistry;
        
    }

	public function createNodeFactory()
    {
        if($this->nodeFactory === null)
        {
            $this->nodeFactory = $this->loadNodes();
        }        

        return $this->nodeFactory;
    }

    protected function loadNodes()
    {
        $file = $this->nodeFile;

        $doLoadNodes = function($content)
        {
            $nodeFactoryParser = new NodeFactoryParser();

            $nodeFactory = $nodeFactoryParser->parse($content);

            return $nodeFactory;
        };

        /* @var $nodeFactory PHPpdf\Node\Factory */
        $nodeFactory = $this->getFromCacheOrCallClosure($file, $doLoadNodes);

        //TODO: DI
        if($nodeFactory->hasPrototype('page') && $nodeFactory->hasPrototype('dynamic-page'))
        {
            $page = $nodeFactory->create('page');
            $nodeFactory->getPrototype('dynamic-page')->setPrototypePage($page);
        }
        
        return $nodeFactory;
    }

    protected function getFromCacheOrCallClosure($file, \Closure $closure)
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

        return $this->getFromCacheOrCallClosure($file, $doLoadEnhancements);
    }

    protected function loadFonts()
    {
        $file = $this->fontFile;

        $doLoadFonts = function($content)
        {
            $fontRegistryParser = new FontRegistryParser();
            $fontRegistry = $fontRegistryParser->parse($content);

            return $fontRegistry;
        };

        return $this->getFromCacheOrCallClosure($file, $doLoadFonts);
    }   
}