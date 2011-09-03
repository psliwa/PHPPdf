<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Node;

use PHPPdf\Document,
    PHPPdf\Formatter\Formatter;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Container extends Node
{
    protected $children = array();
    private $document = null;

    /**
     * @param Node $node Child node object
     * @return PHPPdf\Node\Container
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

    protected function preDraw(Document $document)
    {
        $this->document = $document;

        parent::preDraw($document);
    }

    public function getDocument()
    {
        return $this->document;
    }

    protected function doDraw(Document $document)
    {
        foreach($this->children as $node)
        {
            $tasks = $node->getDrawingTasks($document);
            foreach($tasks as $task)
            {
                $this->addDrawingTask($task);
            }
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
        parent::translate($x, $y);

        foreach($this->getChildren() as $child)
        {
            $child->translate($x, $y);
        }
    }

    /**
     * Splits compose node.
     *
     * @todo refactoring
     *
     * @param integer $height
     * @return \PHPPdf\Node\Node
     */
    protected function doSplit($height)
    {
        $splitCompose = parent::doSplit($height);

        if(!$splitCompose)
        {
            return null;
        }

        $childrenToSplit = array();
        $childrenToMove = array();

        $splitLine = $this->getFirstPoint()->getY() - $height;

        foreach($this->getChildren() as $child)
        {
            $childStart = $child->getFirstPoint()->getY();
            $childEnd = $child->getDiagonalPoint()->getY();

            if($splitLine < $childStart && $splitLine > $childEnd)
            {
                $childrenToSplit[] = $child;
            }
            elseif($splitLine >= $childStart)
            {
                $childrenToMove[] = $child;
            }
        }

        $splitProducts = array();
        $translates = array(0);    
        
        foreach($childrenToSplit as $child)
        {
            $childStart = $child->getFirstPoint()->getY();
            $childEnd = $child->getDiagonalPoint()->getY();
            $childSplitLine = $childStart - $splitLine;
            
            $originalChildHeight = $child->getHeight();
            
            $splitProduct = $child->split($childSplitLine);

            $yChildStart = $child->getFirstPoint()->getY();
            $yChildEnd = $child->getDiagonalPoint()->getY();
            if($splitProduct)
            {
                $heightAfterSplit = $splitProduct->getHeight() + $child->getHeight();
                $translate = $heightAfterSplit - $originalChildHeight;
                $translates[] = $translate + ($yChildEnd - $splitProduct->getFirstPoint()->getY());
                $splitProducts[] = $splitProduct;
            }
            else
            {
                $translates[] = ($yChildStart - $yChildEnd) - ($child->getHeight() - $childSplitLine);
                array_unshift($childrenToMove, $child);
            }
        }

        $splitCompose->removeAll();

        $splitProducts = array_merge($splitProducts, $childrenToMove);
        
        foreach($splitProducts as $child)
        {
            $splitCompose->add($child);
        }        
              
        $translate = \max($translates);

        $boundary = $splitCompose->getBoundary();
        $points = $splitCompose->getBoundary()->getPoints();

        $splitCompose->setHeight($splitCompose->getHeight() + $translate);
        
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
        
        return $splitCompose;
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