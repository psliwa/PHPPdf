<?php

namespace PHPPdf\Test\Configuration;

use PHPPdf\Configuration\LoaderImpl;

class LoaderImplTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    public function saveCacheIfCacheIsEmpty($file, $loaderMethodName)
    {
        $loader = new LoaderImpl();
        
        $nodeFile = $this->readAttribute($loader, 'nodeFile');
        $enhancementFile = $this->readAttribute($loader, 'enhancementFile');
        $fontFile = $this->readAttribute($loader, 'fontFile');
 
        $cache = $this->getMock('PHPPdf\Cache\NullCache', array('test', 'save'));

        $cacheId = $this->invokeMethod($loader, 'getCacheId', array($$file));

        $cache->expects($this->once())
              ->method('test')
              ->with($cacheId)
              ->will($this->returnValue(false));

        $cache->expects($this->once())
              ->method('save');

        $loader->setCache($cache);

        $this->invokeMethod($loader, $loaderMethodName);
    }

    public function configFileGetterProvider()
    {
        return array(
            array('nodeFile', 'createNodeFactory', new PHPPdf\Node\Factory()),
            array('enhancementFile', 'createEnhancementFactory', new \PHPPdf\Enhancement\Factory()),
            array('fontFile', 'createFontRegistry', new PHPPdf\Font\Registry()),
        );
    }

    /**
     * @test
     * @dataProvider configFileGetterProvider
     */
    public function loadCacheIfCacheIsntEmpty($file, $loaderMethodName, $cacheContent)
    {
        $loader = new LoaderImpl();
        
        $nodeFile = $this->readAttribute($loader, 'nodeFile');
        $enhancementFile = $this->readAttribute($loader, 'enhancementFile');
        $fontFile = $this->readAttribute($loader, 'fontFile');

        $cache = $this->getMock('PHPPdf\Cache\NullCache', array('test', 'save', 'load'));

        $cacheId = $this->invokeMethod($loader, 'getCacheId', array($$file));

        $cache->expects($this->once())
              ->method('test')
              ->with($cacheId)
              ->will($this->returnValue(true));

        $cache->expects($this->once())
              ->method('load')
              ->with($cacheId)
              ->will($this->returnValue($cacheContent));

        $loader->setCache($cache);

        $this->invokeMethod($loader, $loaderMethodName);
    }
}