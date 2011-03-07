<?php

use PHPPdf\Parser\FacadeBuilder,
    PHPPdf\Cache\CacheImpl,
    PHPPdf\Parser\FacadeConfiguration;

class FacadeBuilderTest extends TestCase
{
    private $builder;
    private $configuration;

    public function setUp()
    {
        $this->configuration = $this->getMock('PHPPdf\Parser\FacadeConfiguration', array('setGlyphsConfigFile', 'setEnhancementsConfigFile', 'setFormattersConfigFile', 'setFontsConfigFile'));
        $this->builder = FacadeBuilder::create($this->configuration);
    }

    /**
     * @test
     */
    public function returnFacadeOnBuildMethod()
    {
        $this->assertInstanceOf('PHPPdf\Parser\Facade', $this->builder->build());
    }

    /**
     * @test
     * @dataProvider configFileSettersProvider
     */
    public function delegateConfigFileSettersToFacadeConfigurationObject($configFileSetters)
    {
        $at = 0;
        foreach($configFileSetters as $method => $file)
        {
            $this->configuration->expects($this->at($at++))
                                ->method($method)
                                ->with($file);
        }

        foreach($configFileSetters as $method => $arg)
        {
            $this->assertTrue($this->builder === $this->builder->$method($arg));
        }
    }

    public function configFileSettersProvider()
    {
        return array(
            array(array('setGlyphsConfigFile' => 'file1', 'setEnhancementsConfigFile' => 'file2', 'setFormattersConfigFile' => 'file3', 'setFontsConfigFile' => 'file4')),
        );
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
        $facade = $this->builder->setCache(CacheImpl::ENGINE_FILE, array())->build();

        $this->assertInstanceOf('PHPPdf\Cache\CacheImpl', $this->readAttribute($facade, 'cache'));
    }

    /**
     * @test
     * @dataProvider booleanProvider
     */
    public function switchingOnAndOffStylesheetConstraintCache($useCache)
    {
        $facade = $this->builder->setUseStylesheetConstraintCache($useCache)->build();

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
