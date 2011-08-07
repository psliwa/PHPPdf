<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Enhancement;

use PHPPdf\Engine\GraphicsContext;

use PHPPdf\Document;

use PHPPdf\Glyph\Page,
    PHPPdf\Util,
    PHPPdf\Glyph\Glyph;

/**
 * Enhance glyph by drawing background
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

    protected function doEnhance($graphicsContext, Glyph $glyph, Document $document)
    {
        if($this->getColor() !== null)
        {
            $boundary = $this->getBoundary($glyph);
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
            list($x, $y) = $this->getFirstPoint($glyph)->toArray();
            list($endX, $endY) = $this->getDiagonalPoint($glyph)->toArray();
                    
            list($width, $height) = $this->getImageDimension($image, $glyph);

            $graphicsContext->saveGS();
            $graphicsContext->clipRectangle($x, $y, $x+$this->getWidth($glyph), $y-$this->getHeight($glyph));
 
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
    
    private function getImageDimension($image, Glyph $glyph)
    {
        $width = $this->imageWidth;
        $height = $this->imageHeight;
        
        if(!$width && !$height)
        {
            return array($image->getOriginalWidth(), $image->getOriginalHeight());
        }
        
        list($width, $height) = $this->convertPercentageDimension($glyph, $width, $height);
        
        $ratio = $image->getOriginalWidth() / $image->getOriginalHeight();
            
        list($width, $height) = Util::calculateDependantSizes($width, $height, $ratio);
        
        return array($width, $height);
    }
    
    private function convertPercentageDimension(Glyph $glyph, $width, $height)
    {
        $width = Util::convertFromPercentageValue($width, $this->getWidth($glyph));
        $height = Util::convertFromPercentageValue($height, $this->getHeight($glyph));
        
        return array($width, $height);
    }

    private function getFirstPoint(Glyph $glyph)
    {
        if($this->useRealDimension)
        {
            return $glyph->getRealFirstPoint();
        }
        
        return $glyph->getFirstPoint();
    }
    
    private function getDiagonalPoint(Glyph $glyph)
    {
        if($this->useRealDimension)
        {
            return $glyph->getRealDiagonalPoint();
        }
        
        return $glyph->getDiagonalPoint();
    }
    
    private function getBoundary(Glyph $glyph)
    {
        if($this->useRealDimension)
        {
            return $glyph->getRealBoundary();
        }
        
        return $glyph->getBoundary();
    }
    
    private function getWidth(Glyph $glyph)
    {
        if($this->useRealDimension)
        {
            return $glyph->getRealWidth();
        }
        
        return $glyph->getWidth();
    }

    private function getHeight(Glyph $glyph)
    {
        if($this->useRealDimension)
        {
            return $glyph->getRealHeight();
        }
        
        return $glyph->getHeight();
    }
}