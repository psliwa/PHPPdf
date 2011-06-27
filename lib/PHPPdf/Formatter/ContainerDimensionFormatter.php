<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Formatter;

use PHPPdf\Formatter\BaseFormatter,
    PHPPdf\Glyph as Glyphs,
    PHPPdf\Document;

/**
 * Calculates real dimension of compose glyph
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ContainerDimensionFormatter extends BaseFormatter
{
    public function format(Glyphs\Glyph $glyph, Document $document)
    {
        $minX = $maxX = $minY = $maxY = null;
        foreach($glyph->getChildren() as $child)
        {
            $boundary = $child->getBoundary();
            $firstPoint = $boundary->getFirstPoint();
            $diagonalPoint = $boundary->getDiagonalPoint();

            $childMinX = $firstPoint->getX() - $child->getMarginLeft();
            $childMaxX = $diagonalPoint->getX() + $child->getMarginRight();
            $childMinY = $diagonalPoint->getY() - $child->getMarginBottom();
            $childMaxY = $firstPoint->getY() + $child->getMarginTop();

            $this->changeValueIfIsLess($maxX, $childMaxX);
            $this->changeValueIfIsLess($maxY, $childMaxY);

            $this->changeValueIfIsGreater($minX, $childMinX);
            $this->changeValueIfIsGreater($minY, $childMinY);
        }

        $paddingVertical = $glyph->getPaddingTop() + $glyph->getPaddingBottom();
        $paddingHorizontal = $glyph->getPaddingLeft() + $glyph->getPaddingRight();

        $realHeight = $paddingVertical + ($maxY - $minY);
        $realWidth = $paddingHorizontal + ($maxX - $minX);

        $display = $glyph->getAttribute('display');

        if($realHeight > $glyph->getHeight())
        {
            $glyph->setHeight($realHeight);
        }

        if($display === Glyphs\Glyph::DISPLAY_INLINE || $realWidth > $glyph->getWidth())
        {
            $glyph->setWidth($realWidth);
        }
    }

    private function changeValueIfIsLess(&$value, $valueToSet)
    {
        if($value === null || $value < $valueToSet)
        {
            $value = $valueToSet;
        }
    }

    private function changeValueIfIsGreater(&$value, $valueToSet)
    {
        if($value === null || $value > $valueToSet)
        {
            $value = $valueToSet;
        }
    }
}