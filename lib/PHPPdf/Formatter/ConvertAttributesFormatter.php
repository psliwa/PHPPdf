<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Formatter;

use PHPPdf\Formatter\BaseFormatter,
    PHPPdf\Glyph\Glyph,
    PHPPdf\Util,
    PHPPdf\Document;

/**
 * Convert values of some attributes
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ConvertAttributesFormatter extends BaseFormatter
{
    public function format(Glyph $glyph, Document $document)
    {
        $this->convertPercentageDimensions($glyph);
        $this->convertAutoMargins($glyph);
        $this->convertDegreesToRadians($glyph);
    }

    private function convertPercentageDimensions(Glyph $glyph)
    {       
        $glyph->convertScalarAttribute('width');
        $glyph->convertScalarAttribute('height');
    }

    private function convertFromPercentageValue($value, $percent)
    {
        return Util::convertFromPercentageValue($percent, $value);
    }

    private function convertAutoMargins(Glyph $glyph)
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

    private function hasAutoMargins(Glyph $glyph)
    {
        $marginLeft = $glyph->getMarginLeft();
        $marginRight = $glyph->getMarginRight();

        return ($marginLeft === Glyph::MARGIN_AUTO && $marginRight === Glyph::MARGIN_AUTO);
    }
    
    private function convertDegreesToRadians(Glyph $glyph)
    {
        $rotate = $glyph->getAttribute('rotate');
        
        if($rotate !== null && strpos($rotate, 'deg') !== false)
        {
            $degrees = (float) $rotate;
            $radians = deg2rad($degrees);
            $glyph->setAttribute('rotate', $radians);
        }
    }
}