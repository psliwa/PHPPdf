<?php

namespace PHPPdf\Formatter;

use PHPPdf\Formatter\BaseFormatter,
    PHPPdf\Glyph as Glyphs,
    PHPPdf\Document;

/**
 * Calculates real dimension of glyph
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class StandardDimensionFormatter extends BaseFormatter
{
    public function format(Glyphs\Glyph $glyph, Document $document)
    {
        $parent = $glyph->getParent();

        if($glyph->getWidth() === null && $glyph->getAttribute('display') === Glyphs\Glyph::DISPLAY_BLOCK && $glyph->getFloat() === Glyphs\Glyph::FLOAT_NONE)
        {
            $parentWidth = $parent->getWidthWithoutPaddings();

            $marginLeft = $glyph->getMarginLeft();
            $marginRight = $glyph->getMarginRight();

            $glyph->setWidth($parentWidth - ($marginLeft + $marginRight));
            $glyph->setRelativeWidth('100%');
        }
        elseif($glyph->getAttribute('display') === Glyphs\Glyph::DISPLAY_INLINE)
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