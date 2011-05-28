<?php

namespace PHPPdf\Formatter;

use PHPPdf\Glyph\Glyph;

/**
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

    protected function breakSubjectOfSplittingIncraseTranslation($verticalTranslation)
    {
        $this->getSubjectOfSplitting()->createNextPage();
        $this->totalVerticalTranslation += $verticalTranslation;
    }
    
    protected function addChildrenToCurrentPageAndTranslate(Glyph $glyph, $translation)
    {
        $this->getSubjectOfSplitting()->getCurrentPage()->add($glyph);
        $glyph->translate(0, -$translation);
    }
}