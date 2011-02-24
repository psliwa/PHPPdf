<?php

namespace PHPPdf\Formatter;

use PHPPdf\Formatter\BaseFormatter,
    PHPPdf\Glyph as Glyphs,
    PHPPdf\Formatter\Chain;

/**
 * Calculates real dimension of glyph
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class StandardDimensionFormatter extends BaseFormatter
{
    public function preFormat(Glyphs\Glyph $glyph)
    {
        $parent = $glyph->getParent();

        if($glyph->getWidth() === null && $glyph->getAttribute('display') === Glyphs\AbstractGlyph::DISPLAY_BLOCK && $glyph->getAttribute('float') === Glyphs\AbstractGlyph::FLOAT_NONE)
        {
            $parentWidth = $parent->getWidthWithoutPaddings();

            $marginLeft = $glyph->getMarginLeft();
            $marginRight = $glyph->getMarginRight();

            $glyph->setWidth($parentWidth - ($marginLeft + $marginRight));
        }
        elseif($glyph->getAttribute('display') === Glyphs\AbstractGlyph::DISPLAY_INLINE)
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

        $glyph->setWidth($glyph->getWidth() + $paddingLeft + $paddingRight);
        $glyph->setHeight($glyph->getHeight() + $paddingTop + $paddingBottom);
    }
}