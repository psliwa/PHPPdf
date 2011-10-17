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
        $this->configurationLoader = $this->getMockBuilder('PHPPdf\Configuration\Loader')
                                          ->getMock();
        $this->builder = FacadeBuilder::create($this->configurationLoader);
        
        $complexAttributeFactory = $this->getMock('PHPPdf\ComplexAttribute\Factory');
        
        $this->configurationLoader->expects($this->any())
                                  ->method('createComplexAttributeFactory')
                                  ->will($this->returnValue($complexAttributeFactory));
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
    
    /**
     * @test
     * @dataProvider booleanProvider
     */
    public function switchOnAndOffCacheForConfigurationLoader($useCache)
    {
        $this->builder->setUseCacheForConfigurationLoader($useCache);
        $this->builder->setCache('File', array('cache_dir' => TEST_RESOURCES_DIR));
                       
        $this->configurationLoader->expects($useCache ? $this->once() : $this->never())
                                  ->method('setCache');
               
        $this->builder->build();
    }
    
    /**
     * @test
     * @dataProvider injectProperDocumentParserToFacadeProvider
     */
    public function injectProperDocumentParserToFacade($type, $expectedClass)
    {
        $this->builder->setDocumentParserType($type);
        
        $facade = $this->builder->build();
        
        $this->assertInstanceof($expectedClass, $facade->getDocumentParser());
    }
    
    public function injectProperDocumentParserToFacadeProvider()
    {
        return array(
            array(
                FacadeBuilder::PARSER_XML,
                'PHPPdf\Parser\XmlDocumentParser',
            ),
            array(
                FacadeBuilder::PARSER_MARKDOWN,
                'PHPPdf\Parser\MarkdownDocumentParser',
            ),
        );
    }
}
