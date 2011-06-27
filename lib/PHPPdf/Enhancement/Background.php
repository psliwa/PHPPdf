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

    public function __construct($color = null, $image = null, $repeat = self::REPEAT_NONE, $radius = null)
    {
        parent::__construct($color, $radius);

        if($image !== null && !$image instanceof \Zend_Pdf_Resource_Image)
        {
            $image = \Zend_Pdf_Image::imageWithPath($image);
        }

        $this->image = $image;
        $this->repeat = $repeat;
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
            if($this->getRadius() !== null)
            {
                $boundary = $glyph->getBoundary();

                $firstPoint = $boundary[3];
                $diagonalPoint = $boundary[1];
                
                $this->drawRoundedBoundary($graphicsContext, $firstPoint[0], $firstPoint[1], $diagonalPoint[0], $diagonalPoint[1], \Zend_Pdf_Page::SHAPE_DRAW_FILL_AND_STROKE);
            }
            else
            {
                $this->drawBoundary($page->getGraphicsContext(), $glyph->getBoundary(), \Zend_Pdf_Page::SHAPE_DRAW_FILL);
            }
        }

        $image = $this->getImage();
        if($image !== null)
        {
            list($x, $y) = $glyph->getFirstPoint()->toArray();
            list($endX, $endY) = $glyph->getDiagonalPoint()->toArray();

            $height = $image->getPixelHeight();
            $width = $image->getPixelWidth();

            $graphicsContext->saveGS();
            $graphicsContext->clipRectangle($x, $y, $x+$glyph->getWidth(), $y-$glyph->getHeight());

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
}