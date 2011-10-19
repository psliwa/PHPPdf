<?php

namespace PHPPdf\Test\Parser;

use PHPPdf\Parser\StylesheetConstraint;

use PHPPdf\Document;

use PHPPdf\Parser\MarkdownDocumentParser;

use PHPPdf\PHPUnit\Framework\TestCase;

class MarkdownDocumentParserTest extends TestCase
{
    private $markdownParser;
    private $documentParser;
    private $markdownDocumentParser;
    
    public function setUp()
    {
        $this->markdownParser = $this->getMock('PHPPdf\Parser\Parser');
        $this->documentParser = $this->getMock('PHPPdf\Parser\DocumentParser');
        
        $this->markdownDocumentParser = new MarkdownDocumentParser($this->documentParser, $this->markdownParser);
    }
    
    /**
     * @test
     * @dataProvider methodsProvider
     */
    public function delegateMethodInvocationsToInnerDocumentParser($method, $argument)
    {
        $this->documentParser->expects($this->once())
                             ->method($method)
                             ->with($argument);
        $this->markdownDocumentParser->$method($argument);
    }
    
    public function methodsProvider()
    {
        return array(
            array('setNodeFactory', $this->getMock('PHPPdf\Node\Factory')),
            array('setComplexAttributeFactory', $this->getMock('PHPPdf\ComplexAttribute\Factory')),
            array('addListener', $this->getMock('PHPPdf\Parser\DocumentParserListener')),
            array('setDocument', new Document()),
        );
    }
    
    /**
     * @test
     */
    public function getNodeManagerInvokesTheSameMethodOfInnerDocumentParser()
    {
        $nodeManager = $this->getMock('PHPPdf\Node\Manager');
        
        $this->documentParser->expects($this->once())
                             ->method('getNodeManager')
                             ->will($this->returnValue($nodeManager));
                             
        $this->assertEquals($nodeManager, $this->markdownDocumentParser->getNodeManager());
    }
    
    /**
     * @test
     */
    public function parseInvokesMarkdownParserAndInnerDocumentParser()
    {
        $markdown = 'some markdown';
        $markdownParserOutput = 'markdown parser output';
        $innerDocumentParserOutput = 'inner document parser output';
        
        $this->markdownParser->expects($this->once())
                             ->method('parse')
                             ->with($markdown)
                             ->will($this->returnValue($markdownParserOutput));

        $this->documentParser->expects($this->once())
                             ->method('parse')
                             ->with($this->stringContains($markdownParserOutput))
                             ->will($this->returnValue($innerDocumentParserOutput));
                             
        $this->assertEquals($innerDocumentParserOutput, $this->markdownDocumentParser->parse($markdown));
    }
    
    /**
     * @test
     */
    public function useFacadeToCreateStylesheetConstraint()
    {
        $stylesheetConstraint = new StylesheetConstraint();
        
        $facade = $this->getMockBuilder('PHPPdf\Parser\Facade')
                       ->setMethods(array('retrieveStylesheetConstraint'))
                       ->disableOriginalConstructor()
                       ->getMock();
                       
        $this->markdownDocumentParser->setFacade($facade);
        
        $facade->expects($this->once())
               ->method('retrieveStylesheetConstraint')
               ->with($this->isInstanceOf('PHPPdf\Core\DataSource'))
               ->will($this->returnValue($stylesheetConstraint));
               
        $this->documentParser->expects($this->once())
                             ->method('parse')
                             ->with($this->isType('string'), $stylesheetConstraint);
                             
        $this->markdownDocumentParser->parse('markdown');
    }
}