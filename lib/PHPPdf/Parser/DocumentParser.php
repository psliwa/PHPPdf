<?php

namespace PHPPdf\Parser;

use PHPPdf\Document;
use PHPPdf\Node\Factory as NodeFactory;
use PHPPdf\Enhancement\Factory as EnhancementFactory;

interface DocumentParser extends Parser
{
    public function setNodeFactory(NodeFactory $factory);
    
    public function setEnhancementFactory(EnhancementFactory $enhancementFactory);
    
    public function addListener(DocumentParserListener $listener);
    
    /**
     * @return NodeManager
     */
    public function getNodeManager();
    
    public function setDocument(Document $document);
}