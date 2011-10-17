<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Parser;

use PHPPdf\Document;
use PHPPdf\Node\Factory as NodeFactory;
use PHPPdf\ComplexAttribute\Factory as ComplexAttributeFactory;

/**
 * Document praser interface
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface DocumentParser extends Parser
{
    public function setNodeFactory(NodeFactory $factory);
    
    public function setComplexAttributeFactory(ComplexAttributeFactory $complexAttributeFactory);
    
    public function addListener(DocumentParserListener $listener);
    
    /**
     * @return NodeManager
     */
    public function getNodeManager();
    
    public function setDocument(Document $document);
}