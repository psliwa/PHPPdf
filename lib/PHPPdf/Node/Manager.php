<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Node;

use PHPPdf\Parser\Exception\DuplicatedIdException;

/**
 * Manager of nodes
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Manager
{
    private $nodes = array();
    private $wrappers = array();
    
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
}