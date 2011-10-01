<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Node;

use PHPPdf\Util\DrawingTaskHeap;
use PHPPdf\Document,
    PHPPdf\Node\Container,
    PHPPdf\Formatter\Formatter;

/**
 * Collection of the pages
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class PageCollection extends Container
{
    public function getAttribute($name)
    {
        return null;
    }

    public function setAttribute($name, $value)
    {
        return $this;
    }

    public function breakAt($height)
    {
        throw new \LogicException('PageCollection can\'t be broken.');
    }
    
    public function getGraphicsContext()
    {
        return null;
    }
    
    public function getAllDrawingTasks(Document $document)
    {
        $tasks = new DrawingTaskHeap();
        $this->collectOrderedDrawingTasks($document, $tasks);
        $this->collectUnorderedDrawingTasks($document, $tasks);
        $this->collectPostDrawingTasks($document, $tasks);
        
        return $tasks;
    }
    
    public function collectPostDrawingTasks(Document $document, DrawingTaskHeap $tasks)
    {
        foreach($this->getChildren() as $child)
        {
            $child->collectPostDrawingTasks($document, $tasks);
        }
    }
}