<?php

use PHPPdf\Parser\Facade;

class FacadeTest extends PHPUnit_Framework_TestCase
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
}