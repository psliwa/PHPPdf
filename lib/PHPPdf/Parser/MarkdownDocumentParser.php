<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Parser;

use PHPPdf\Bridge\Markdown\MarkdownParser;
use PHPPdf\Document;
use PHPPdf\Node\Factory as NodeFactory;
use PHPPdf\Enhancement\Factory as EnhancementFactory;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class MarkdownDocumentParser implements DocumentParser
{
    const DOCUMENT_TEMPLATE = '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE pdf SYSTEM "%resources%/dtd/doctype.dtd"><pdf><dynamic-page>%MARKDOWN%</dynamic-page></pdf>';
    
    private $documentParser;
    private $markdownParser;
    
    public function __construct(DocumentParser $documentParser, Parser $markdownParser = null)
    {        
        $this->documentParser = $documentParser;        
        $this->markdownParser = $markdownParser ? : new MarkdownParser();
    }
    
    public function parse($markdownDocument)
    {
        $markdownOutput = $this->markdownParser->parse($markdownDocument);
        
        $relativePathToResources = str_replace('\\', '/', realpath(__DIR__.'/../Resources'));
        
        $markdownOutput = str_replace('%MARKDOWN%', $markdownOutput, str_replace('%resources%', $relativePathToResources, self::DOCUMENT_TEMPLATE));
        
        return $this->documentParser->parse($markdownOutput);
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