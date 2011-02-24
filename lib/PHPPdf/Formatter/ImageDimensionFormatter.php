<?php

namespace PHPPdf\Formatter;

use PHPPdf\Glyph as Glyphs;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ImageDimensionFormatter extends BaseFormatter
{
    public function preFormat(Glyphs\Glyph $glyph)
    {
        if($this->isImageAndSizesArentSet($glyph))
        {
            $parent = $glyph->getParent();

            $width = !$glyph->getWidth() ? $parent->getWidth() : $glyph->getWidth();
            $height = !$glyph->getHeight() ? $parent->getHeight() : $glyph->getHeight();

            $src = $glyph->getAttribute('src');
            
            $srcWidth = $originalWidth = $src->getPixelWidth();
            $srcHeight = $originalHeight = $src->getPixelHeight();

            if($srcWidth > $width)
            {
                $srcWidth = $width;
            }

            if($srcHeight > $height)
            {
                $srcHeight = $height;
            }

            $ratio = $originalWidth/$originalHeight;
            $srcRatio = !$srcHeight ? 0 : $srcWidth/$srcHeight;
            
            if($srcRatio > $ratio)
            {
                $srcWidth = $ratio * $srcHeight;
            }
            elseif($srcRatio < $ratio)
            {
                $srcHeight = $ratio * $srcWidth;
            }
            $glyph->setWidth($srcWidth);
            $glyph->setHeight($srcHeight);
        }
    }

    private function isImageAndSizesArentSet(Glyphs\Glyph $glyph)
    {
        return ($glyph instanceof Glyphs\Image && (!$glyph->getWidth() || !$glyph->getHeight()));
    }
}