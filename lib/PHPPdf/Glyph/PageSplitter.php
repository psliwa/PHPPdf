<?php

namespace PHPPdf\Glyph;

/**
 * Object of this class has ability to split glyphs into pages. This class works on
 * DynamicPage instance.
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class PageSplitter extends AbstractSplitter
{
    protected function shouldParentBeAutomaticallyBroken(Glyph $glyph)
    {
        return $glyph->getPageBreak();
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