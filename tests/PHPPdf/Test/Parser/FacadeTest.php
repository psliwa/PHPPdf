<?php

namespace PHPPdf\Test\Parser;

use PHPPdf\Configuration\LoaderImpl;
use PHPPdf\Parser\Facade,
    PHPPdf\Parser\FacadeConfiguration;

class FacadeTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $facade;
    
    private $loaderMock;
    private $documentParser;

    public function setUp()
    {
        $this->loaderMock = $this->getMockBuilder('PHPPdf\Configuration\Loader')
                                 ->getMock();
        $this->documentParser = $this->getMock('PHPPdf\Parser\DocumentParser');
        $this->stylesheetParser = $this->getMockBuilder('PHPPdf\Parser\StylesheetParser')
                                       ->setMethods(array('parse'))
                                       ->disableOriginalConstructor()
                                       ->getMock();
        $this->facade = new Facade($this->loaderMock, $this->documentParser, $this->stylesheetParser);
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

        $document = new \PHPPdf\Document();
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

        $documentMock = $this->getMockBuilder('PHPPdf\Document')
                             ->setMethods(array('draw', 'initialize', 'render', 'addFontDefinitions', 'setComplexAttributeFactory'))
                             ->getMock();

        $stylesheetParserMock = $this->getMock('PHPPdf\Parser\StylesheetParser', array('parse'));
        $constraintMock = $this->getMock('PHPPdf\Parser\StylesheetConstraint');
        $pageCollectionMock = $this->getMock('PHPPdf\Node\PageCollection', array());
        
        $nodeFactoryMock = $this->getMock('PHPPdf\Node\Factory');
        $complexAttributeFactoryMock = $this->getMock('PHPPdf\ComplexAttribute\Factory');
        $fontDefinitionsStub = array('some-data');
        
        $this->loaderMock->expects($this->atLeastOnce())
                         ->method('createNodeFactory')
                         ->will($this->returnValue($nodeFactoryMock));
        $this->loaderMock->expects($this->atLeastOnce())
                         ->method('createComplexAttributeFactory')
                         ->will($this->returnValue($complexAttributeFactoryMock));
        $this->loaderMock->expects($this->atLeastOnce())
                         ->method('createFontRegistry')
                         ->will($this->returnValue($fontDefinitionsStub));
                         
        $documentMock->expects($this->once())
                     ->method('addFontDefinitions')
                     ->with($fontDefinitionsStub);
        $documentMock->expects($this->once())
                     ->method('setComplexAttributeFactory')
                     ->with($complexAttributeFactoryMock);
        $this->documentParser->expects($this->once())
                             ->method('setComplexAttributeFactory')
                             ->with($complexAttributeFactoryMock);
        $this->documentParser->expects($this->once())
                             ->method('setNodeFactory')
                             ->with($nodeFactoryMock);

        $this->documentParser->expects($this->once())
                             ->method('parse')
                             ->with($this->equalTo($xml), $this->equalTo($constraintMock))
                             ->will($this->returnValue($pageCollectionMock));

        $this->stylesheetParser->expects($this->once())
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
        $facade = new Facade(new LoaderImpl(), $this->documentParser, $this->stylesheetParser);

        $cache = $this->getMock('PHPPdf\Cache\NullCache', array('test', 'save', 'load'));
        $cache->expects($this->exactly($numberOfCacheMethodInvoking))
              ->method('test')
              ->will($this->returnValue(false));
        $cache->expects($this->exactly(0))
              ->method('load');
        $cache->expects($this->exactly($numberOfCacheMethodInvoking))
              ->method('save');

        $this->documentParser->expects($this->once())
                             ->method('parse')
                             ->will($this->returnValue(new \PHPPdf\Node\PageCollection()));

        $this->stylesheetParser->expects($this->once())
                                 ->method('parse')
                                 ->will($this->returnValue(new \PHPPdf\Parser\CachingStylesheetConstraint()));


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