<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Enhancement;

use PHPPdf\Glyph\Page,
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

    public function __construct($color = null, $image = null, $repeat = self::REPEAT_NONE, $radius = null, $useRealDimension = false)
    {
        parent::__construct($color, $radius);

        if($image !== null && !$image instanceof \Zend_Pdf_Resource_Image)
        {
            $image = \Zend_Pdf_Image::imageWithPath($image);
        }

        $this->image = $image;
        $this->setRepeat($repeat);
        $this->useRealDimension = (boolean) $useRealDimension;
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

    /**
     * @return \Zend_Pdf_Resource_Image
     */
    public function getImage()
    {
        return $this->image;
    }

    protected function doEnhance(Page $page, Glyph $glyph)
    {
        $graphicsContext = $page->getGraphicsContext();

        if($this->getColor() !== null)
        {
            $boundary = $this->getBoundary($glyph);
            if($this->getRadius() !== null)
            {
                $firstPoint = $boundary[3];
                $diagonalPoint = $boundary[1];
                
                $this->drawRoundedBoundary($graphicsContext, $firstPoint[0], $firstPoint[1], $diagonalPoint[0], $diagonalPoint[1], \Zend_Pdf_Page::SHAPE_DRAW_FILL_AND_STROKE);
            }
            else
            {
                $this->drawBoundary($page->getGraphicsContext(), $boundary, \Zend_Pdf_Page::SHAPE_DRAW_FILL);
            }
        }

        $image = $this->getImage();
        if($image !== null)
        {
            list($x, $y) = $this->getFirstPoint($glyph)->toArray();
            list($endX, $endY) = $this->getDiagonalPoint($glyph)->toArray();

            $height = $image->getPixelHeight();
            $width = $image->getPixelWidth();

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