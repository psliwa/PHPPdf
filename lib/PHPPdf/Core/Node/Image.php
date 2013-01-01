<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node;

use PHPPdf\Exception\InvalidResourceException;

use PHPPdf\Core\Engine\EmptyImage;

use PHPPdf\Core\Engine\Engine;

use PHPPdf\Core\DrawingTaskHeap;

use PHPPdf\Core\Document,
    PHPPdf\Core\DrawingTask,
    PHPPdf\Core\Node\Node;

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
        static::addAttribute('ignore-error', false);
        static::addAttribute('keep-ratio', false);
    }
    
    protected static function initializeType()
    {
        static::setAttributeSetters(array('ignore-error' => 'setIgnoreError'));
        static::setAttributeSetters(array('keep-ratio' => 'setKeepRatio'));
        
        parent::initializeType();
    }
    
    public function setIgnoreError($flag)
    {
        $flag = $this->filterBooleanValue($flag);
        $this->setAttributeDirectly('ignore-error', $flag);
    }

    public function setKeepRatio($flag)
    {
        $flag = $this->filterBooleanValue($flag);
        $this->setAttributeDirectly('keep-ratio', $flag);
    }
    
    protected function doDraw(Document $document, DrawingTaskHeap $tasks)
    {
        $sourceImage = $this->createSource($document);
        $callback = function(Node $node, $sourceImage)
        {
            $gc = $node->getGraphicsContext();
            
            $alpha = $node->getAlpha();
            $isAlphaSet = $alpha != 1 && $alpha !== null;
            $keepRatio = $node->getAttribute('keep-ratio');
            
            $rotationNode = $node->getAncestorWithRotation();
            $translation = $node->getPositionTranslation();
        
            if($isAlphaSet || $rotationNode || $keepRatio)
            {
                $gc->saveGS();
                $gc->setAlpha($alpha);
            }
            
            if($rotationNode)
            {
                $middlePoint = $rotationNode->getMiddlePoint()->translate($translation->getX(), $translation->getY());
                $gc->rotate($middlePoint->getX(), $middlePoint->getY(), $rotationNode->getRotate());
            }
            
            $height = $originalHeight = $node->getHeight();
            $width = $originalWidth = $node->getWidth();
            
            list($x, $y) = $node->getStartDrawingPoint();
            
            $x += $translation->getX();
            $y -= $translation->getY();
            
            if($keepRatio)
            {
                $gc->clipRectangle($x, $y, $x+$originalWidth, $y-$originalHeight);
                
                $sourceRatio = $sourceImage->getOriginalHeight() / $sourceImage->getOriginalWidth();
                
                if($sourceRatio > 1)
                {
                    $height = $width * $sourceRatio;
                    
                    $y += ($height - $originalHeight)/2;
                }
                else
                {
                    $width = $height / $sourceRatio;
                    
                    $x -= ($width - $originalWidth)/2;
                }
            }
            
            $gc->drawImage($sourceImage, $x, $y-$height, $x+$width, $y);
            
            if($isAlphaSet || $rotationNode || $keepRatio)
            {
                $gc->restoreGS();
            }
        };
        
        $drawingTask = new DrawingTask($callback, array($this, $sourceImage));

        $tasks->insert($drawingTask);
    }
    
    public function createSource(Engine $engine)
    {
        try
        {
            return $engine->createImage($this->getAttribute('src'));
        }
        catch(InvalidResourceException $e)
        {
            if($this->getAttribute('ignore-error'))
            {
                return EmptyImage::getInstance();
            }
            
            throw $e;
        }
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