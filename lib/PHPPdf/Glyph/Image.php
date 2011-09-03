<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph;

use PHPPdf\Document,
    PHPPdf\Util\DrawingTask,
    PHPPdf\Glyph\Glyph;

/**
 * Image element
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Image extends Glyph
{
    public function initialize()
    {
        parent::initialize();
        $this->addAttribute('src');
    }

    protected function doDraw(Document $document)
    {
        $callback = function($glyph)
        {
            $gc = $glyph->getGraphicsContext();
            
            $alpha = $glyph->getAlpha();
            $isAlphaSet = $alpha != 1 && $alpha !== null;
            
            $rotationGlyph = $glyph->getAncestorWithRotation();
        
            if($isAlphaSet || $rotationGlyph)
            {
                $gc->saveGS();
                $gc->setAlpha($alpha);
            }
            
            if($rotationGlyph)
            {
                $middlePoint = $rotationGlyph->getMiddlePoint();
                $gc->rotate($middlePoint->getX(), $middlePoint->getY(), $rotationGlyph->getRotate());
            }

            list($x, $y) = $glyph->getStartDrawingPoint();
            $image = $glyph->getAttribute('src');
            $gc->drawImage($image, $x, $y-$glyph->getHeight(), $x+$glyph->getWidth(), $y);
            
            if($isAlphaSet || $rotationGlyph)
            {
                $gc->restoreGS();
            }
        };
        
        $drawingTask = new DrawingTask($callback, array($this));

        $this->addDrawingTask($drawingTask);
    }

    public function preFormat(Document $document)
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

    public function split($height)
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