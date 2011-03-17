<?php

namespace PHPPdf\Formatter;

use PHPPdf\Formatter\BaseFormatter,
    PHPPdf\Glyph as Glyphs,
    PHPPdf\Document;

/**
 * Convert values of some attributes
 *
 * @todo change name *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ConvertDimensionFormatter extends BaseFormatter
{
    public function format(Glyphs\Glyph $glyph, Document $document)
    {
        if(!$glyph instanceof Glyphs\Page)
        {
            $this->convertPercentageDimensions($glyph);
            $this->convertAutoMargins($glyph);
        }
        $this->convertColorAttributes($glyph);
        $this->convertFontType($glyph, $document);
    }

    private function convertPercentageDimensions(Glyphs\Glyph $glyph)
    {
        $parent = $glyph->getParent();

        $parentWidth = $parent->getWidthWithoutPaddings();
        $parentHeight = $parent->getHeightWithoutPaddings();

        $width = $glyph->getWidth();
        $height = $glyph->getHeight();

        $glyph->setWidth($this->convertFromPercentageValue($parentWidth, $width));
        $glyph->setHeight($this->convertFromPercentageValue($parentHeight, $height));
    }

    private function convertFromPercentageValue($value, $percent)
    {
        $len = strlen($percent);
        if(substr($percent, $len-1) === '%')
        {
            $percent = (double) substr($percent, 0, $len-1);

            $percent = $value*$percent / 100;
        }
        return $percent;
    }

    private function convertAutoMargins(Glyphs\Glyph $glyph)
    {
        $parent = $glyph->getParent();

        $parentWidth = $parent->getWidthWithoutPaddings();

        if($this->hasAutoMargins($glyph))
        {
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

        return ($marginLeft === Glyphs\AbstractGlyph::MARGIN_AUTO && $marginRight === Glyphs\AbstractGlyph::MARGIN_AUTO);
    }

    private function convertColorAttributes(Glyphs\Glyph $glyph)
    {
        $colorAttributes = array('color');

        foreach($colorAttributes as $attribute)
        {
            $this->convertColorAttribute($glyph, $attribute);
        }
    }

    private function convertColorAttribute(Glyphs\Glyph $glyph, $attribute)
    {
        if($glyph->hasAttribute($attribute))
        {
            $color = $glyph->getAttribute($attribute);
            if(is_string($color))
            {
                $color = new \Zend_Pdf_Color_Html($color);
                $glyph->setAttribute($attribute, $color);
            }
        }
    }

    private function convertFontType(Glyphs\Glyph $glyph, Document $document)
    {
        $font = $glyph->getAttribute('font-type');
        if($font && is_string($font))
        {
            $font = $document->getFontRegistry()->get($font);
            $glyph->setAttribute('font-type', $font);
        }
    }
}