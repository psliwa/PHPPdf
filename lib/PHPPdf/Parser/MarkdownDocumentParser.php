<?php

namespace PHPPdf\Parser;

use PHPPdf\Document;

use PHPPdf\Node\Factory as NodeFactory;
use PHPPdf\Enhancement\Factory as EnhancementFactory;

class MarkdownDocumentParser implements DocumentParser
{
    const DOCUMENT_TEMPLATE = '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE pdf SYSTEM "%resources%/dtd/doctype.dtd"><pdf><dynamic-page>%MARKDOWN%</dynamic-page></pdf>';
    
    private $documentParser;
    
    public function __construct(XmlDocumentParser $documentParser)
    {
        if(!function_exists('Markdown'))
        {
            $markdownPath = __DIR__.'/../../vendor/Markdown/markdown.php';
            if(file_exists($markdownPath))
            {
                require_once $markdownPath;
            }
            else
            {
                throw new \Exception('PHP Markdown library not found. Mabey you should call "> php vendors.php" command from root dir of PHPPdf library to download dependencies?');
            }
        }
        
        $this->documentParser = $documentParser;
    }
    
    public function parse($markdownDocument)
    {
        $html = \Markdown($markdownDocument);
        
        $relativePathToResources = str_replace('\\', '/', realpath(__DIR__.'/../Resources'));
        
        $xml = str_replace('%MARKDOWN%', $html, str_replace('%resources%', $relativePathToResources, self::DOCUMENT_TEMPLATE));
        
        return $this->documentParser->parse($xml);
    }
    
    public function setNodeFactory(NodeFactory $factory)
    {
        $this->documentParser->setNodeFactory($factory);
    }
    
    public function setEnhancementFactory(EnhancementFactory $enhancementFactory)
    {
        $this->documentParser->setEnhancementFactory($enhancementFactory);
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