<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Formatter;

use PHPPdf\Formatter\BaseFormatter,
    PHPPdf\Glyph as Glyphs,
    PHPPdf\Util,
    PHPPdf\Document;

/**
 * Convert values of some attributes
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ConvertAttributesFormatter extends BaseFormatter
{
    public function format(Glyphs\Glyph $glyph, Document $document)
    {
        $this->convertPercentageDimensions($glyph);
        $this->convertAutoMargins($glyph);
        $this->convertColorAttributes($glyph, $document);
    }

    private function convertPercentageDimensions(Glyphs\Glyph $glyph)
    {       
        $glyph->convertScalarAttribute('width');
        $glyph->convertScalarAttribute('height');
    }

    private function convertFromPercentageValue($value, $percent)
    {
        return Util::convertFromPercentageValue($percent, $value);
    }

    private function convertAutoMargins(Glyphs\Glyph $glyph)
    {
        $parent = $glyph->getParent();

        if($parent !== null && $this->hasAutoMargins($glyph))
        {
            $parentWidth = $parent->getWidthWithoutPaddings();
            $glyphWidth = $glyph->getWidth();

            if($glyphWidth > $parentWidth)
            {
                $parentWidth = $glyphWidth;
                $parent->setWidth($glyphWidth);
            }

            $glyph->hadAutoMargins(true);
            $width = $glyph->getWidth() === null ? $parentWidth : $glyph->getWidth();
            
            //adds horizontal paddings, becouse dimension formatter hasn't executed yet
            $width += $glyph->getPaddingLeft() + $glyph->getPaddingRight();

            $margin = ($parentWidth - $width)/2;
            $glyph->setMarginLeft($margin);
            $glyph->setMarginRight($margin);
        }
    }

    private function hasAutoMargins(Glyphs\Glyph $glyph)
    {
        $marginLeft = $glyph->getMarginLeft();
        $marginRight = $glyph->getMarginRight();

        return ($marginLeft === Glyphs\Glyph::MARGIN_AUTO && $marginRight === Glyphs\Glyph::MARGIN_AUTO);
    }

    private function convertColorAttributes(Glyphs\Glyph $glyph, Document $document)
    {
        $colorAttributes = array('color');

        foreach($colorAttributes as $attribute)
        {
            $this->convertColorAttribute($glyph, $attribute, $document);
        }
    }

    private function convertColorAttribute(Glyphs\Glyph $glyph, $attribute, Document $document)
    {
        if($glyph->hasAttribute($attribute))
        {
            $color = $glyph->getAttribute($attribute);
            if(is_string($color))
            {
                $color = $document->createColor($color);
                $glyph->setAttribute($attribute, $color);
            }
        }
    }
}