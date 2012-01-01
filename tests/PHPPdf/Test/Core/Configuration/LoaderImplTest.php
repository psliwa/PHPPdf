<?php

namespace PHPPdf\Test\Core\Configuration;

use PHPPdf\Core\Configuration\LoaderImpl;

class LoaderImplTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    public function saveCacheIfCacheIsEmpty($file, $loaderMethodName)
    {
        $loader = new LoaderImpl();
        
        $nodeFile = $this->readAttribute($loader, 'nodeFile');
        $complexAttributeFile = $this->readAttribute($loader, 'complexAttributeFile');
        $fontFile = $this->readAttribute($loader, 'fontFile');
        $colorFile = $this->readAttribute($loader, 'colorFile');
 
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
            array('nodeFile', 'createNodeFactory', new \PHPPdf\Core\Node\NodeFactory()),
            array('complexAttributeFile', 'createComplexAttributeFactory', new \PHPPdf\Core\ComplexAttribute\ComplexAttributeFactory()),
            array('fontFiles', 'createFontRegistry', array()),
            array('colorFile', 'createColorPalette', array()),
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
        $complexAttributeFile = $this->readAttribute($loader, 'complexAttributeFile');
        $fontFiles = $this->readAttribute($loader, 'fontFiles');
        $colorFile = $this->readAttribute($loader, 'colorFile');

        $cache = $this->getMock('PHPPdf\Cache\NullCache', array('test', 'save', 'load'));

        $cacheId = $this->invokeMethod($loader, 'getCacheId', array(is_array($$file) ? current($$file) : $$file));

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