<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node;

use PHPPdf\Core\DrawingTaskHeap;

use PHPPdf\Core\Document,
    PHPPdf\Core\Formatter\Formatter;

/**
 * Standard container element
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Container extends Node
{
    private $children = array();

    /**
     * @param Node $node Child node object
     * @return PHPPdf\Core\Node\Container
     */
    public function add(Node $node)
    {
        $node->setParent($this);
        $node->reset();
        $this->children[] = $node;
        $node->setPriorityFromParent();

        return $this;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function remove(Node $node)
    {
        foreach($this->children as $key => $child)
        {
            if($node === $child)
            {
                unset($this->children[$key]);
                return true;
            }
        }

        return false;
    }

    public function removeAll()
    {
        $this->children = array();
    }

    public function reset()
    {
        parent::reset();

        foreach($this->children as $child)
        {
            $child->reset();
        }
    }

    protected function preDraw(Document $document, DrawingTaskHeap $tasks)
    {
        parent::preDraw($document, $tasks);
    }

    protected function doDraw(Document $document, DrawingTaskHeap $tasks)
    {
        foreach($this->children as $node)
        {
            $node->collectOrderedDrawingTasks($document, $tasks);
        }
    }

    public function copy()
    {
        $copy = parent::copy();

        foreach($this->children as $key => $child)
        {
            $clonedChild = $child->copy();
            $copy->children[$key] = $clonedChild;
            $clonedChild->setParent($copy);
        }

        return $copy;
    }

    public function translate($x, $y)
    {
        if(!$x && !$y)
        {
            return;
        }
        
        parent::translate($x, $y);

        foreach($this->getChildren() as $child)
        {
            $child->translate($x, $y);
        }
    }

    /**
     * Breaks compose node.
     *
     * @todo refactoring
     *
     * @param integer $height
     * @return \PHPPdf\Core\Node\Node
     */
    protected function doBreakAt($height)
    {
        $brokenCompose = parent::doBreakAt($height);

        if(!$brokenCompose)
        {
            return null;
        }

        $childrenToBreak = array();
        $childrenToMove = array();

        $breakLine = $this->getFirstPoint()->getY() - $height;

        foreach($this->getChildren() as $child)
        {
            $childStart = $child->getFirstPoint()->getY();
            $childEnd = $child->getDiagonalPoint()->getY();

            if($breakLine < $childStart && $breakLine > $childEnd)
            {
                $childrenToBreak[] = $child;
            }
            elseif($breakLine >= $childStart)
            {
                $childrenToMove[] = $child;
            }
        }

        $breakProducts = array();
        $translates = array(0);    
        
        foreach($childrenToBreak as $child)
        {
            $childStart = $child->getFirstPoint()->getY();
            $childEnd = $child->getDiagonalPoint()->getY();
            $childBreakingLine = $childStart - $breakLine;
            
            $originalChildHeight = $child->getHeight();
            
            $breakingProduct = $child->breakAt($childBreakingLine);

            $yChildStart = $child->getFirstPoint()->getY();
            $yChildEnd = $child->getDiagonalPoint()->getY();
            if($breakingProduct)
            {
                $heightAfterBreaking = $breakingProduct->getHeight() + $child->getHeight();
                $translate = $heightAfterBreaking - $originalChildHeight;
                $translates[] = $translate + ($yChildEnd - $breakingProduct->getFirstPoint()->getY());
                $breakProducts[] = $breakingProduct;
            }
            else
            {
                $translates[] = ($yChildStart - $yChildEnd) - ($child->getHeight() - $childBreakingLine);
                array_unshift($childrenToMove, $child);
            }
        }

        $brokenCompose->removeAll();

        $breakProducts = array_merge($breakProducts, $childrenToMove);
        
        foreach($breakProducts as $child)
        {
            $brokenCompose->add($child);
        }        
              
        $translate = \max($translates);

        $boundary = $brokenCompose->getBoundary();
        $points = $brokenCompose->getBoundary()->getPoints();

        $brokenCompose->setHeight($brokenCompose->getHeight() + $translate);
        
        $boundary->reset();
        $boundary->setNext($points[0])
                 ->setNext($points[1])
                 ->setNext($points[2]->translate(0, $translate))
                 ->setNext($points[3]->translate(0, $translate))
                 ->close();

        foreach($childrenToMove as $child)
        {
            $child->translate(0, $translate);
        }
        
        return $brokenCompose;
    }

    public function getMinWidth()
    {
        $minWidth = $this->getAttributeDirectly('min-width');

        foreach($this->getChildren() as $child)
        {
            $minWidth = max(array($minWidth, $child->getMinWidth()));
        }

        return $minWidth + $this->getPaddingLeft() + $this->getPaddingRight() + $this->getMarginLeft() + $this->getMarginRight();
    }
    
    public function hasLeafDescendants($bottomYCoord = null)
    {
        foreach($this->getChildren() as $child)
        {
            $hasValidPosition = $bottomYCoord === null || $child->isAbleToExistsAboveCoord($bottomYCoord);

            if($hasValidPosition && ($child->isLeaf() || $child->hasLeafDescendants()))
            {
                return true;
            }
        }

        return false;
    }
}