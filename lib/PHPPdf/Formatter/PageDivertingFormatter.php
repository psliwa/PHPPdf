<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Formatter;

use PHPPdf\Glyph\Glyph;

/**
 * TODO: refactoring and rename
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class PageDivertingFormatter extends AbstractDivertingFormatter
{
    protected function shouldParentBeAutomaticallyBroken(Glyph $glyph)
    {
        return $glyph->getAttribute('page-break');
    }

    protected function addToSubjectOfSplitting(Glyph $glyph)
    {
        $this->getSubjectOfSplitting()->getCurrentPage()->add($glyph);
    }

    protected function breakSubjectOfSplittingIncraseTranslation(Glyph $glyph, $glyphYCoordStart, $gapBeetwenBottomOfOriginalGlyphAndEndOfPage)
    {
        $translation = $this->glyph->getPage()->getHeight() + $this->glyph->getPage()->getMarginBottom() - $glyphYCoordStart;
        $verticalTranslation = $translation - $gapBeetwenBottomOfOriginalGlyphAndEndOfPage;
        
        $this->getSubjectOfSplitting()->createNextPage();
        $this->totalVerticalTranslation += $verticalTranslation;
        
        $this->getSubjectOfSplitting()->getCurrentPage()->add($glyph);
        $glyph->translate(0, -$translation);
    }
}