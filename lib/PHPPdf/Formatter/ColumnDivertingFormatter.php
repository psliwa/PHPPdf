<?php

namespace PHPPdf\Formatter;

use PHPPdf\Glyph\Glyph;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ColumnDivertingFormatter extends AbstractDivertingFormatter
{
    protected function shouldParentBeAutomaticallyBroken(Glyph $glyph)
    {
        return false;
    }

    protected function addToSubjectOfSplitting(Glyph $glyph)
    {
        $this->getSubjectOfSplitting()->getCurrentContainer()->add($glyph);
    }

    protected function breakSubjectOfSplittingIncraseTranslation($verticalTranslation)
    {
        $this->getSubjectOfSplitting()->createNextContainer();

        $numberOfContainers = $this->getSubjectOfSplitting()->getContainers();
        $numberOfColumns = $this->getSubjectOfSplitting()->getAttribute('number-of-columns');

        if(($numberOfContainers % $numberOfColumns) == 0)
        {
            $this->totalVerticalTranslation += $verticalTranslation;
        }
    }

    protected function addChildrenToCurrentPageAndTranslate(Glyph $glyph, $translation)
    {
        $this->getSubjectOfSplitting()->getCurrentContainer()->add($glyph);
        $glyph->translate(0, -$translation);
    }
}