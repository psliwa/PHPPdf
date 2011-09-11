<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Node;

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
    
    protected function doDraw(Document $document)
    {
        $callback = function($node)
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
            $image = $node->getAttribute('src');
            $gc->drawImage($image, $x, $y-$node->getHeight(), $x+$node->getWidth(), $y);
            
            if($isAlphaSet || $rotationNode)
            {
                $gc->restoreGS();
            }
        };
        
        $drawingTask = new DrawingTask($callback, array($this));

        return array($drawingTask);
    }

    protected function beforeFormat(Document $document)
    {
        $src = $this->getAttribute('src');

        if(is_string($src))
        {
            $src = $document->createImage($src);
            $this->setAttribute('src', $src);
        }
        
        if(!$this->getWidth() && !$this->getHeight())
        {

            $this->setWidth($src->getOriginalWidth());
            $this->setHeight($src->getOriginalHeight());
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