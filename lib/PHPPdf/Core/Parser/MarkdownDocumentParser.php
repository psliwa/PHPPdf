<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Parser;

use PHPPdf\Parser\Parser;
use PHPPdf\DataSource\DataSource;
use PHPPdf\Bridge\Markdown\MarkdownParser;
use PHPPdf\Core\Document;
use PHPPdf\Core\Node\NodeFactory;
use PHPPdf\Core\ComplexAttribute\ComplexAttributeFactory;
use PHPPdf\Core\FacadeAware;
use PHPPdf\Core\Facade;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class MarkdownDocumentParser implements DocumentParser, FacadeAware
{
    private $documentParser;
    private $markdownParser;
    private $stylesheetFilepath;
    private $documentTemplateFilepath;
    private $facade;
    
    public function __construct(DocumentParser $documentParser, Parser $markdownParser = null)
    {        
        $this->documentParser = $documentParser;        
        $this->markdownParser = $markdownParser ? : new MarkdownParser();
    }
    
    public function setFacade(Facade $facade)
    {
        $this->facade = $facade;
    }
    
    public function parse($markdownDocument)
    {
        $markdownOutput = $this->markdownParser->parse($markdownDocument);
        
        $relativePathToResources = str_replace('\\', '/', realpath(__DIR__.'/../../Resources'));
        
        $documentTemplateSource = DataSource::fromFile($this->documentTemplateFilepath ? : __DIR__.'/../../Resources/markdown/document.xml');
        
        $markdownOutput = str_replace('%MARKDOWN%', $markdownOutput, str_replace('%resources%', $relativePathToResources, $documentTemplateSource->read()));
        
        return $this->documentParser->parse($markdownOutput, $this->getStylesheetConstraint());
    }
    
    public function setStylesheetFilepath($filepath)
    {
        $this->stylesheetFilepath = $filepath;
    }
    
    public function setDocumentTemplateFilepath($filepath)
    {
        $this->documentTemplateFilepath = $filepath;
    }
    
    private function getStylesheetConstraint()
    {
        if($this->facade)
        {
            $markdownStylesheet = DataSource::fromFile($this->stylesheetFilepath ? : __DIR__.'/../../Resources/markdown/stylesheet.xml');

            return $this->facade->retrieveStylesheetConstraint($markdownStylesheet);
        }

        return null;
    }

    public function setNodeFactory(NodeFactory $factory)
    {
        $this->documentParser->setNodeFactory($factory);
    }
    
    public function setComplexAttributeFactory(ComplexAttributeFactory $complexAttributeFactory)
    {
        $this->documentParser->setComplexAttributeFactory($complexAttributeFactory);
    }
    
    public function addListener(DocumentParserListener $listener)
    {
        $this->documentParser->addListener($listener);
    }
    
    public function getNodeManager()
    {
        return $this->documentParser->getNodeManager();
    }
    
    public function setDocument(Document $document)
    {
        $this->documentParser->setDocument($document);
    }
}