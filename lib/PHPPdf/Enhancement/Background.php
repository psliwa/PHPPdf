<?php

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

    public function __construct($color = null, $image = null, $repeat = self::REPEAT_NONE)
    {
        parent::__construct($color);

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
        if($this->getColor() !== null)
        {
            $this->drawBoundary($page->getGraphicsContext(), $glyph->getBoundary(), \Zend_Pdf_Page::SHAPE_DRAW_FILL);
        }

        $image = $this->getImage();
        if($image !== null)
        {
            $graphicsContext = $page->getGraphicsContext();

            list($x, $y) = $glyph->getBoundary()->getFirstPoint()->toArray();
            list($endX, $endY) = $glyph->getBoundary()->getDiagonalPoint()->toArray();

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