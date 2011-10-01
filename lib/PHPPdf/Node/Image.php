<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Node;

use PHPPdf\Util\DrawingTaskHeap;

use PHPPdf\Document,
    PHPPdf\Util\DrawingTask,
    PHPPdf\Node\Node;

/**
 * Image element
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Image extends Node
{
    protected static function setDefaultAttributes()
    {
        parent::setDefaultAttributes();
        
        static::addAttribute('src');
    }
    
    protected function doDraw(Document $document, DrawingTaskHeap $tasks)
    {
        $sourceImage = $document->createImage($this->getAttribute('src'));
        $callback = function($node, $sourceImage)
        {
            $gc = $node->getGraphicsContext();
            
            $alpha = $node->getAlpha();
            $isAlphaSet = $alpha != 1 && $alpha !== null;
            
            $rotationNode = $node->getAncestorWithRotation();
        
            if($isAlphaSet || $rotationNode)
            {
                $gc->saveGS();
                $gc->setAlpha($alpha);
            }
            
            if($rotationNode)
            {
                $middlePoint = $rotationNode->getMiddlePoint();
                $gc->rotate($middlePoint->getX(), $middlePoint->getY(), $rotationNode->getRotate());
            }

            list($x, $y) = $node->getStartDrawingPoint();
            $gc->drawImage($sourceImage, $x, $y-$node->getHeight(), $x+$node->getWidth(), $y);
            
            if($isAlphaSet || $rotationNode)
            {
                $gc->restoreGS();
            }
        };
        
        $drawingTask = new DrawingTask($callback, array($this, $sourceImage));

        $tasks->insert($drawingTask);
    }

    protected function beforeFormat(Document $document)
    {
        if(!$this->getWidth() && !$this->getHeight())
        {
            $src = $this->getAttribute('src');
    
            if(is_string($src))
            {
                $source = $document->createImage($src);
            }
            else
            {
                $source = $src;
            }

            $this->setWidth($source->getOriginalWidth());
            $this->setHeight($source->getOriginalHeight());
        }
    }

    public function breakAt($height)
    {
        return null;
    }
    
    public function getMinWidth()
    {
        return $this->getWidth() + $this->getMarginLeft() + $this->getMarginRight();
    }
    
    public function isLeaf()
    {
        return true;
    }
    
    protected function isAbleToExistsAboveCoord($yCoord)
    {
        $yCoord += $this->getHeight();
        return $this->getFirstPoint()->getY() > $yCoord;
    }
}