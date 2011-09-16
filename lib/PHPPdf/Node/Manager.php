<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Node;

use PHPPdf\Document;

use PHPPdf\Parser\DocumentParserListener;
use PHPPdf\Parser\Exception\DuplicatedIdException;

/**
 * Manager of nodes
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Manager implements DocumentParserListener
{
    private $nodes = array();
    private $wrappers = array();
    
    private $managedNodes = array();
    
    private $disableListening = 0;
    
    public function register($id, Node $node)
    {
        if(isset($this->nodes[$id]))
        {
            throw new DuplicatedIdException(sprintf('Duplicate of id "%s".', $id));
        }
        
        $this->nodes[$id] = $node;

        if(isset($this->wrappers[$id]))
        {
            $this->wrappers[$id]->setNode($node);
        }
    }
    
    /**
     * @return NodeAware
     */
    public function get($id)
    {
        if(isset($this->nodes[$id]))
        {
            return $this->nodes[$id];
        }
        
        if(isset($this->wrappers[$id]))
        {
            return $this->wrappers[$id];
        }
        
        $wrapper = new NodeWrapper();
        
        $this->wrappers[$id] = $wrapper;
        
        return $wrapper;
    }
    
    public function clear()
    {
        $this->wrappers = array();
        $this->flushAll($this->nodes);
        $this->nodes = array();
        $this->flush();
    }
    
    public function attach(Node $node)
    {
        $this->managedNodes[] = $node;
    }
    
    public function flush()
    {
        $this->flushAll($this->managedNodes);
        
        $this->managedNodes = array();
    }
    
    private function flushAll(array $nodes)
    {
        foreach($nodes as $node)
        {
            $node->flush();
        }
    }
    
    public function onEndParsePlaceholders(Document $document, Node $node)
    {
        if($this->isPage($node))
        {
            $node->preFormat($document);
        }
    }
    
    public function onStartParseNode(Document $document, Node $node)
    {

    }
    
    private function isPage($node)
    {
        return $node instanceof \PHPPdf\Node\Page;
    }
    
    public function onEndParseNode(Document $document, Node $node)
    {
        if(!$this->isPage($node) && $this->isPage($node->getParent()))
        {
            $node->format($document);
        }

        if($this->isPage($node))
        {
            $node->postFormat($document);
        }
    }
}