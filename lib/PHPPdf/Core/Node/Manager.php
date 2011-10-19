<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node;

use PHPPdf\Core\DrawingTaskHeap;

use PHPPdf\Core\Document;
use PHPPdf\Core\Parser\DocumentParserListener;
use PHPPdf\Core\Parser\Exception\DuplicatedIdException;

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
    private $behavioursTasks;
    
    public function __construct()
    {
        $this->behavioursTasks = new DrawingTaskHeap();
    }
    
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
        $this->behavioursTasks = new DrawingTaskHeap();
        $this->wrappers = array();
        $this->nodes = array();
    }
    
    public function onEndParsePlaceholders(Document $document, PageCollection $root, Node $node)
    {
        if($this->isPage($node))
        {
            $node->preFormat($document);
        }
    }
    
    public function onStartParseNode(Document $document, PageCollection $root, Node $node)
    {
    }
    
    private function isPage($node)
    {
        return $node instanceof \PHPPdf\Core\Node\Page;
    }
    
    public function onEndParseNode(Document $document, PageCollection $root, Node $node)
    {
        if(!$this->isPage($node) && $this->isPage($node->getParent()))
        {
            $node->format($document);
            $node->collectUnorderedDrawingTasks($document, $this->behavioursTasks);
        }
        
        $this->processDynamicPage($document, $node);
              
        if($this->isPage($node))
        {
            $node->postFormat($document);
            
            $tasks = new DrawingTaskHeap();
            if(!$this->isDynamicPage($node) || count($node->getPages()) > 0)
            {
                $node->collectOrderedDrawingTasks($document, $tasks);
            }
            $node->collectPostDrawingTasks($document, $tasks);
            $document->invokeTasks($tasks);
            
            $node->flush();
            $root->flush();
        }
    }
    
    private function processDynamicPage(Document $document, Node $node)
    {
        if($this->isDynamicPage($node->getParent()) && $this->isOutOfPage($node))
        {
            $dynamicPage = $node->getParent();
            $dynamicPage->postFormat($document);
            
            $pages = $dynamicPage->getAllPagesExceptsCurrent();
            
            foreach($pages as $page)
            {
                $tasks = new DrawingTaskHeap();
                $page->collectOrderedDrawingTasks($document, $tasks);
                $document->invokeTasks($tasks);
                $page->flush();
            }

            $currentPage = $dynamicPage->getCurrentPage(false);
            $dynamicPage->removeAllPagesExceptsCurrent();
            
            if($currentPage)
            {
                $dynamicPage->removeAll();
                foreach($currentPage->getChildren() as $child)
                {
                    $child->setAttribute('break', false);
                    $dynamicPage->add($child);
                }
                $currentPage->removeAll();
            }
        }
    }
    
    private function isDynamicPage($node)
    {
        return $node instanceof \PHPPdf\Core\Node\DynamicPage;
    }
    
    private function isOutOfPage(Node $node)
    {
        $page = $node->getParent();
        
        if($node->getAttribute('break'))
        {
            return true;
        }
        
        return $page->getDiagonalPoint()->getY() > $node->getFirstPoint()->getY() && $node->getFloat() == Node::FLOAT_NONE;
    }
    
    public function onEndParsing(Document $document, PageCollection $root)
    {
        $document->invokeTasks($this->behavioursTasks);
        $this->behavioursTasks = new DrawingTaskHeap();
    }
}