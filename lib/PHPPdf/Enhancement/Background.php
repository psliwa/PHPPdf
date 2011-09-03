<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Enhancement;

use PHPPdf\Engine\GraphicsContext;

use PHPPdf\Document;

use PHPPdf\Node\Page,
    PHPPdf\Util,
    PHPPdf\Node\Node;

/**
 * Enhance node by drawing background
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Background extends Enhancement
{
    const REPEAT_NONE = 0;
    const REPEAT_X = 1;
    const REPEAT_Y = 2;
    const REPEAT_ALL = 3;

    private $image = null;
    private $repeat;
    private $useRealDimension;
    private $imageWidth = null;
    private $imageHeight = null;

    public function __construct($color = null, $image = null, $repeat = self::REPEAT_NONE, $radius = null, $useRealDimension = false, $imageWidth = null, $imageHeight = null)
    {
        parent::__construct($color, $radius);

        $this->image = $image;
        $this->setRepeat($repeat);
        $this->useRealDimension = Util::convertBooleanValue($useRealDimension);
        $this->setImageDimension($imageWidth, $imageHeight);
    }
    
    private function setRepeat($repeat)
    {
        if(!is_numeric($repeat))
        {
            $repeat = $this->getConstantValue('REPEAT', $repeat);
        }
        
        $this->repeat = $repeat;
    }
    
    public function getRepeat()
    {
        return $this->repeat;
    }
    
    private function setImageDimension($width, $height)
    {
        if($this->image === null)
        {
            return;
        }
        
        $this->imageWidth = $width;
        $this->imageHeight = $height;
    }

    public function getImage()
    {
        return $this->image;
    }

    protected function doEnhance($graphicsContext, Node $node, Document $document)
    {
        if($this->getColor() !== null)
        {
            $boundary = $this->getBoundary($node);
            if($this->getRadius() !== null)
            {
                $firstPoint = $boundary[3];
                $diagonalPoint = $boundary[1];
                
                $this->drawRoundedBoundary($graphicsContext, $firstPoint[0], $firstPoint[1], $diagonalPoint[0], $diagonalPoint[1], GraphicsContext::SHAPE_DRAW_FILL_AND_STROKE);
            }
            else
            {
                $this->drawBoundary($graphicsContext, $boundary, GraphicsContext::SHAPE_DRAW_FILL);
            }
        }

        $image = $this->getImage();
        $image = $image ? $document->createImage($image) : null;

        if($image !== null)
        {
            list($x, $y) = $this->getFirstPoint($node)->toArray();
            list($endX, $endY) = $this->getDiagonalPoint($node)->toArray();
                    
            list($width, $height) = $this->getImageDimension($image, $node);

            $graphicsContext->saveGS();
            $graphicsContext->clipRectangle($x, $y, $x+$this->getWidth($node), $y-$this->getHeight($node));
 
            $repeatX = $this->repeat & self::REPEAT_X;
            $repeatY = $this->repeat & self::REPEAT_Y;

            $currentX = $x;
            $currentY = $y;

            do
            {
                $currentY = $y;
                do
                {
                    $graphicsContext->drawImage($image, $currentX, $currentY-$height, $currentX+$width, $currentY);
                    $currentY -= $height;
                }
                while($repeatY && $currentY > $endY);

                $currentX += $width;
            }
            while($repeatX && $currentX < $endX);
            
            $graphicsContext->restoreGS();
        }
    }
    
    private function getImageDimension($image, Node $node)
    {
        $width = $this->imageWidth;
        $height = $this->imageHeight;
        
        if(!$width && !$height)
        {
            return array($image->getOriginalWidth(), $image->getOriginalHeight());
        }
        
        list($width, $height) = $this->convertPercentageDimension($node, $width, $height);
        
        $ratio = $image->getOriginalWidth() / $image->getOriginalHeight();
            
        list($width, $height) = Util::calculateDependantSizes($width, $height, $ratio);
        
        return array($width, $height);
    }
    
    private function convertPercentageDimension(Node $node, $width, $height)
    {
        $width = Util::convertFromPercentageValue($width, $this->getWidth($node));
        $height = Util::convertFromPercentageValue($height, $this->getHeight($node));
        
        return array($width, $height);
    }

    private function getFirstPoint(Node $node)
    {
        if($this->useRealDimension)
        {
            return $node->getRealFirstPoint();
        }
        
        return $node->getFirstPoint();
    }
    
    private function getDiagonalPoint(Node $node)
    {
        if($this->useRealDimension)
        {
            return $node->getRealDiagonalPoint();
        }
        
        return $node->getDiagonalPoint();
    }
    
    private function getBoundary(Node $node)
    {
        if($this->useRealDimension)
        {
            return $node->getRealBoundary();
        }
        
        return $node->getBoundary();
    }
    
    private function getWidth(Node $node)
    {
        if($this->useRealDimension)
        {
            return $node->getRealWidth();
        }
        
        return $node->getWidth();
    }

    private function getHeight(Node $node)
    {
        if($this->useRealDimension)
        {
            return $node->getRealHeight();
        }
        
        return $node->getHeight();
    }
}