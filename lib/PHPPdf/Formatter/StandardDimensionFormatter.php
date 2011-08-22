<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Formatter;

use PHPPdf\Formatter\BaseFormatter,
    PHPPdf\Glyph\Glyph,
    PHPPdf\Document;

/**
 * Calculates real dimension of glyph
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class StandardDimensionFormatter extends BaseFormatter
{
    public function format(Glyph $glyph, Document $document)
    {
        $parent = $glyph->getParent();

        if($glyph->getWidth() === null && $glyph->getAttribute('display') === Glyph::DISPLAY_BLOCK && $glyph->getFloat() === Glyph::FLOAT_NONE)
        {
            $parentWidth = $parent->getWidthWithoutPaddings();

            $marginLeft = $glyph->getMarginLeft();
            $marginRight = $glyph->getMarginRight();

            $glyph->setWidth($parentWidth - ($marginLeft + $marginRight));
            $glyph->setRelativeWidth('100%');
        }
        elseif($glyph->getAttribute('display') === Glyph::DISPLAY_INLINE)
        {
            $glyph->setWidth(0);
        }

        if($glyph->getHeight() === null)
        {
            $glyph->setHeight(0);
        }

        $paddingLeft = $glyph->getPaddingLeft();
        $paddingRight = $glyph->getPaddingRight();
        $paddingTop = $glyph->getPaddingTop();
        $paddingBottom = $glyph->getPaddingBottom();
        
        $prefferedWidth = $glyph->getRealWidth() + $paddingLeft + $paddingRight;
        
        $parent = $glyph->getParent();
        
        $parentWidth = $parent ? $parent->getWidthWithoutPaddings() : null;
        
        if($parent && $parentWidth < $prefferedWidth)
        {
            $prefferedWidth = $parentWidth;
        }

        $glyph->setWidth($prefferedWidth);
        $glyph->setHeight($glyph->getRealHeight() + $paddingTop + $paddingBottom);
    }
}