<?php

namespace PHPPdf\Test\Parser;

use PHPPdf\Parser\FacadeBuilder,
    PHPPdf\Cache\CacheImpl,
    PHPPdf\Parser\FacadeConfiguration;

class FacadeBuilderTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $builder;
    private $configurationLoader;

    public function setUp()
    {
        $this->configurationLoader = $this->getMock('PHPPdf\Configuration\Loader', array('createNodeFactory', 'createEnhancementFactory', 'createFontRegistry', 'setCache'));
        $this->builder = FacadeBuilder::create($this->configurationLoader);
    }

    /**
     * @test
     */
    public function returnFacadeOnBuildMethod()
    {
        $facade = $this->builder->build();
        $this->assertInstanceOf('PHPPdf\Parser\Facade', $facade);
        $configurationLoader = $this->readAttribute($facade, 'configurationLoader');
        $this->assertTrue($configurationLoader === $this->configurationLoader);
    }

    /**
     * @test
     */
    public function cacheIsOffByDefault()
    {
        $facade = $this->builder->build();

        $this->assertInstanceOf('PHPPdf\Cache\NullCache', $this->readAttribute($facade, 'cache'));
    }

    /**
     * @test
     */
    public function settingCacheConfiguration()
    {
        $this->configurationLoader->expects($this->once())
                                  ->method('setCache');
        $facade = $this->builder->setCache(CacheImpl::ENGINE_FILE, array())->build();

        $this->assertInstanceOf('PHPPdf\Cache\CacheImpl', $this->readAttribute($facade, 'cache'));
    }

    /**
     * @test
     * @dataProvider booleanProvider
     */
    public function switchingOnAndOffStylesheetConstraintCache($useCache)
    {
        $facade = $this->builder->setUseCacheForStylesheetConstraint($useCache)->build();

        $this->assertEquals($useCache, $this->readAttribute($facade, 'useCacheForStylesheetConstraint'));
    }

    public function booleanProvider()
    {
        return array(
            array(false),
            array(true),
        );
    }
}
