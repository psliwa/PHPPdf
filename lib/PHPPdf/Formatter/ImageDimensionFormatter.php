<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Formatter;

use PHPPdf\Util;

use PHPPdf\Glyph as Glyphs,
    PHPPdf\Document;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ImageDimensionFormatter extends BaseFormatter
{
    public function format(Glyphs\Glyph $glyph, Document $document)
    {
        if($this->isImageAndSizesArentSet($glyph))
        {
            $width = $glyph->getWidth();
            $height = $glyph->getHeight();
            $src = $glyph->getAttribute('src');

            $originalWidth = $src->getPixelWidth();
            $originalHeight = $src->getPixelHeight();
            $originalRatio = $originalWidth/$originalHeight;

            if(!$width && !$height)
            {
                list($width, $height) = $this->setDimensionsFromParent($glyph);
            }
            
            list($width, $height) = Util::calculateDependantSizes($width, $height, $originalRatio);
            
            $glyph->setWidth($width);
            $glyph->setHeight($height);
        }
    }

    private function isImageAndSizesArentSet(Glyphs\Glyph $glyph)
    {
        return ($glyph instanceof Glyphs\Image && (!$glyph->getWidth() || !$glyph->getHeight()));
    }

    private function setDimensionsFromParent(Glyphs\Glyph $glyph)
    {
        $parent = $glyph->getParent();
        $src = $glyph->getAttribute('src');

        $width = $src->getPixelWidth();
        $height = $src->getPixelHeight();

        if($width > $parent->getWidth() || $height > $parent->getHeight())
        {
            if($parent->getWidth() > $parent->getHeight())
            {
                $height = $parent->getHeight();
                $width = null;
            }
            else
            {
                $width = $parent->getWidth();
                $height = null;
            }
        }

        return array($width, $height);
    }
}