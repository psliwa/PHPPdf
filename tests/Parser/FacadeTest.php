<?php

use PHPPdf\Parser\Facade,
    PHPPdf\Parser\FacadeConfiguration;

class FacadeTest extends TestCase
{
    private $facade;

    public function setUp()
    {
        $this->facade = new Facade();
    }

    /**
     * @test
     */
    public function facadeHaveDefaultsParsers()
    {
        $this->assertInstanceOf('PHPPdf\Parser\DocumentParser', $this->facade->getDocumentParser());
        $this->assertInstanceOf('PHPPdf\Parser\StylesheetParser', $this->facade->getStylesheetParser());
    }

    /**
     * @test
     */
    public function parsersMayByInjectedFromOutside()
    {
        $documentParser = $this->getMock('PHPPdf\Parser\DocumentParser');
        $stylesheetParser = $this->getMock('PHPPdf\Parser\StylesheetParser');

        $this->facade->setDocumentParser($documentParser);
        $this->facade->setStylesheetParser($stylesheetParser);

        $this->assertTrue($this->facade->getDocumentParser() === $documentParser);
        $this->assertTrue($this->facade->getStylesheetParser() === $stylesheetParser);
    }

    /**
     * @test
     */
    public function gettingAndSettingPdf()
    {
        $this->assertInstanceOf('PHPPdf\Document', $this->facade->getDocument());

        $document = new PHPPdf\Document();
        $this->facade->setDocument($document);

        $this->assertTrue($this->facade->getDocument() === $document);

    }

    /**
     * @test
     */
    public function drawingProcess()
    {
        $xml = '<pdf></pdf>';
        $stylesheet = '<stylesheet></stylesheet>';
        $content = 'pdf content';

        $documentMock = $this->getMock('PHPPdf\Document', array('draw', 'initialize', 'render'));
        $parserMock = $this->getMock('PHPPdf\Parser\DocumentParser', array('parse'));
        $stylesheetParserMock = $this->getMock('PHPPdf\Parser\StylesheetParser', array('parse'));
        $constraintMock = $this->getMock('PHPPdf\Parser\StylesheetConstraint');
        $pageCollectionMock = $this->getMock('PHPPdf\Glyph\PageCollection', array());

        $parserMock->expects($this->once())
                   ->method('parse')
                   ->with($this->equalTo($xml), $this->equalTo($constraintMock))
                   ->will($this->returnValue($pageCollectionMock));

        $stylesheetParserMock->expects($this->once())
                             ->method('parse')
                             ->with($this->equalTo($stylesheet))
                             ->will($this->returnValue($constraintMock));

        $documentMock->expects($this->at(0))
                ->method('draw')
                ->with($this->equalTo($pageCollectionMock));
        $documentMock->expects($this->at(1))
                ->method('render')
                ->will($this->returnValue($content));
        $documentMock->expects($this->at(2))
                ->method('initialize');

        $this->facade->setDocumentParser($parserMock);
        $this->facade->setStylesheetParser($stylesheetParserMock);
        $this->facade->setDocument($documentMock);

        $result = $this->facade->render($xml, $stylesheet);

        $this->assertEquals($content, $result);
    }

    /**
     * @test
     * @dataProvider configFileGetterProvider
     */
    public function saveCacheIfCacheIsEmpty($configFileGetterMethodName, $loaderMethodName)
    {
        $configuration = new PHPPdf\Parser\FacadeConfiguration();

        $facade = new Facade($configuration);
        $cache = $this->getMock('PHPPdf\Cache\NullCache', array('test', 'save'));

        $cacheId = $this->invokeMethod($facade, 'getCacheId', array($configuration->$configFileGetterMethodName()));

        $cache->expects($this->once())
              ->method('test')
              ->with($cacheId)
              ->will($this->returnValue(false));

        $cache->expects($this->once())
              ->method('save');

        $facade->setCache($cache);

        $this->invokeMethod($facade, $loaderMethodName);
    }

    public function configFileGetterProvider()
    {
        return array(
            array('getGlyphsConfigFile', 'loadGlyphs', new PHPPdf\Glyph\Factory()),
            array('getEnhancementsConfigFile', 'loadEnhancements', new \PHPPdf\Enhancement\Factory()),
            array('getFontsConfigFile', 'loadFonts', new PHPPdf\Font\Registry()),
            array('getFormattersConfigFile', 'loadFormatters', array()),
        );
    }
    
    /**
     * @test
     * @dataProvider configFileGetterProvider
     */
    public function loadCacheIfCacheIsntEmpty($configFileGetterMethodName, $loaderMethodName, $cacheContent)
    {
        $configuration = new PHPPdf\Parser\FacadeConfiguration();

        $facade = new Facade($configuration);
        $cache = $this->getMock('PHPPdf\Cache\NullCache', array('test', 'save', 'load'));

        $cacheId = $this->invokeMethod($facade, 'getCacheId', array($configuration->$configFileGetterMethodName()));

        $cache->expects($this->once())
              ->method('test')
              ->with($cacheId)
              ->will($this->returnValue(true));

        $cache->expects($this->once())
              ->method('load')
              ->with($cacheId)
              ->will($this->returnValue($cacheContent));

        $facade->setCache($cache);

        $this->invokeMethod($facade, $loaderMethodName);
    }

    /**
     * @test
     * @dataProvider stylesheetCachingParametersProvider
     */
    public function dontCacheStylesheetConstraintByDefault($numberOfCacheMethodInvoking, $useCache)
    {
        $facade = new Facade();

        //load config files owing to test stylesheet constraint caching
        $this->invokeMethod($facade, 'load');

        $cache = $this->getMock('PHPPdf\Cache\NullCache', array('test', 'save', 'load'));
        $cache->expects($this->exactly($numberOfCacheMethodInvoking))
              ->method('test')
              ->will($this->returnValue(false));
        $cache->expects($this->exactly(0))
              ->method('load');
        $cache->expects($this->exactly($numberOfCacheMethodInvoking))
              ->method('save');

        $documentParserMock = $this->getMock('PHPPdf\Parser\DocumentParser', array('parse'));
        $documentParserMock->expects($this->once())
                           ->method('parse')
                           ->will($this->returnValue(new \PHPPdf\Glyph\PageCollection()));

        $stylesheetParserMock = $this->getMock('PHPPdf\Parser\StylesheetParser', array('parse'));
        $stylesheetParserMock->expects($this->once())
                             ->method('parse')
                             ->will($this->returnValue(new PHPPdf\Parser\StylesheetConstraint()));

        $facade->setDocumentParser($documentParserMock);
        $facade->setStylesheetParser($stylesheetParserMock);
        $facade->setCache($cache);

        $facade->setUseCacheForStylesheetConstraint($useCache);

        $facade->render('<pdf></pdf>', '<stylesheet></stylesheet>');
    }

    public function stylesheetCachingParametersProvider()
    {
        return array(
            array(0, false),
            array(1, true),
        );
    }
}