<?php

use PHPPdf\Configuration\LoaderImpl;
use PHPPdf\Parser\Facade,
    PHPPdf\Parser\FacadeConfiguration;

class FacadeTest extends TestCase
{
    private $facade;
    
    private $loaderMock;

    public function setUp()
    {
        $this->loaderMock = $this->getMock('PHPPdf\Configuration\Loader', array('createGlyphFactory', 'createEnhancementFactory', 'createFontRegistry', 'setCache'));
        $this->facade = new Facade($this->loaderMock);
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

        $documentMock = $this->getMock('PHPPdf\Document', array('draw', 'initialize', 'render', 'addFontDefinitions', 'setEnhancementFactory'));
        $parserMock = $this->getMock('PHPPdf\Parser\DocumentParser', array('parse', 'setEnhancementFactory', 'setGlyphFactory'));
        $stylesheetParserMock = $this->getMock('PHPPdf\Parser\StylesheetParser', array('parse'));
        $constraintMock = $this->getMock('PHPPdf\Parser\StylesheetConstraint');
        $pageCollectionMock = $this->getMock('PHPPdf\Glyph\PageCollection', array());
        
        $glyphFactoryMock = $this->getMock('PHPPdf\Glyph\Factory');
        $enhancementFactoryMock = $this->getMock('PHPPdf\Enhancement\Factory');
        $fontDefinitionsStub = array('some-data');
        
        $this->loaderMock->expects($this->atLeastOnce())
                         ->method('createGlyphFactory')
                         ->will($this->returnValue($glyphFactoryMock));
        $this->loaderMock->expects($this->atLeastOnce())
                         ->method('createEnhancementFactory')
                         ->will($this->returnValue($enhancementFactoryMock));
        $this->loaderMock->expects($this->atLeastOnce())
                         ->method('createFontRegistry')
                         ->will($this->returnValue($fontDefinitionsStub));
                         
        $documentMock->expects($this->once())
                     ->method('addFontDefinitions')
                     ->with($fontDefinitionsStub);
        $documentMock->expects($this->once())
                     ->method('setEnhancementFactory')
                     ->with($enhancementFactoryMock);
        $parserMock->expects($this->once())
                   ->method('setEnhancementFactory')
                   ->with($enhancementFactoryMock);
        $parserMock->expects($this->once())
                   ->method('setGlyphFactory')
                   ->with($glyphFactoryMock);

        $parserMock->expects($this->once())
                   ->method('parse')
                   ->with($this->equalTo($xml), $this->equalTo($constraintMock))
                   ->will($this->returnValue($pageCollectionMock));

        $stylesheetParserMock->expects($this->once())
                             ->method('parse')
                             ->with($this->equalTo($stylesheet))
                             ->will($this->returnValue($constraintMock));

        $documentMock->expects($this->at(2))
                ->method('draw')
                ->with($this->equalTo($pageCollectionMock));
        $documentMock->expects($this->at(3))
                ->method('render')
                ->will($this->returnValue($content));
        $documentMock->expects($this->at(4))
                ->method('initialize');

        $this->facade->setDocumentParser($parserMock);
        $this->facade->setStylesheetParser($stylesheetParserMock);
        $this->facade->setDocument($documentMock);

        $result = $this->facade->render($xml, $stylesheet);

        $this->assertEquals($content, $result);
    }
    
    /**
     * @test
     * @dataProvider stylesheetCachingParametersProvider
     */
    public function dontCacheStylesheetConstraintByDefault($numberOfCacheMethodInvoking, $useCache)
    {
        $facade = new Facade(new LoaderImpl());

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
                             ->will($this->returnValue(new PHPPdf\Parser\CachingStylesheetConstraint()));

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