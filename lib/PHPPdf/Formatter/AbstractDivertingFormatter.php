<?php

namespace PHPPdf\Formatter;

use PHPPdf\Document,
    PHPPdf\Glyph\Glyph;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
abstract class AbstractDivertingFormatter extends BaseFormatter
{
    private $glyph;
    protected $totalVerticalTranslation = 0;

    /**
     * @return Glyph
     */
    protected function getSubjectOfSplitting()
    {
        return $this->glyph;
    }

    public function format(Glyph $glyph, Document $document)
    {
        $this->glyph = $glyph;
        $this->totalVerticalTranslation = 0;

        foreach($this->glyph->getChildren() as $child)
        {
            $this->splitChildIfNecessary($child);
        }

        $this->postFormat();
    }

    private function splitChildIfNecessary(Glyph $glyph)
    {
        $childHasBeenSplitted = false;
        $childMayBeSplitted = true;
        $glyph->translate(0, -$this->totalVerticalTranslation);

        if($this->shouldParentBeAutomaticallyBroken($glyph))
        {
            $pageYCoordEnd = $glyph->getDiagonalPoint()->getY() + 1;
        }
        else
        {
            $pageYCoordEnd = $this->glyph->getPage()->getDiagonalPoint()->getY();
        }

        do
        {
            if($this->shouldBeSplited($glyph, $pageYCoordEnd))
            {
                $glyph = $this->splitChildAndGetProductOfSplitting($glyph);
                $childHasBeenSplitted = true;
            }
            else
            {
                if(!$childHasBeenSplitted)
                {
                    $this->addToSubjectOfSplitting($glyph);
                }

                $childMayBeSplitted = false;
            }
        }
        while($childMayBeSplitted);
    }

    /**
     * @return boolean
     */
    abstract protected function shouldParentBeAutomaticallyBroken(Glyph $glyph);

    private function shouldBeSplited(Glyph $glyph, $pageYCoordEnd)
    {
        $yEnd = $glyph->getDiagonalPoint()->getY();

        return ($yEnd < $pageYCoordEnd);
    }

    private function splitChildAndGetProductOfSplitting(Glyph $glyph)
    {
        $originalHeight = $glyph->getFirstPoint()->getY() - $glyph->getDiagonalPoint()->getY();
        $glyphYCoordStart = $this->getChildYCoordOfFirstPoint($glyph);
        $splitLine = $glyphYCoordStart - $this->glyph->getPage()->getDiagonalPoint()->getY();
        $splittedGlyph = $glyph->split($splitLine);

        $heightAfterSplit = $glyph->getFirstPoint()->getY() - $glyph->getDiagonalPoint()->getY();

        $gapBeetwenBottomOfOriginalGlyphAndEndOfPage = 0;

        if($splittedGlyph)
        {
            $gapBeetwenBottomOfOriginalGlyphAndEndOfPage = $glyph->getDiagonalPoint()->getY() - $this->glyph->getPage()->getDiagonalPoint()->getY();
            $glyphYCoordStart = $splittedGlyph->getFirstPoint()->getY();
            $this->addToSubjectOfSplitting($glyph);
            $heightAfterSplit += $splittedGlyph->getFirstPoint()->getY() - $splittedGlyph->getDiagonalPoint()->getY();
            $glyph = $splittedGlyph;
        }

        $translation = $this->getGlyphTranslation($glyph, $glyphYCoordStart);

        $this->breakSubjectOfSplittingIncraseTranslation($translation - $gapBeetwenBottomOfOriginalGlyphAndEndOfPage);
        $this->addChildrenToCurrentPageAndTranslate($glyph, $translation);

        return $glyph;
    }

    private function getChildYCoordOfFirstPoint(Glyph $glyph)
    {
        $yCoordOfFirstPoint = $glyph->getFirstPoint()->getY();

        return $yCoordOfFirstPoint;
    }

    protected function getGlyphTranslation(Glyph $glyph, $glyphYCoordStart)
    {
        $translation = $this->glyph->getPage()->getHeight() + $this->glyph->getPage()->getMarginBottom() - $glyphYCoordStart;

        return $translation;
    }

    abstract protected function addChildrenToCurrentPageAndTranslate(Glyph $glyph, $translation);

    abstract protected function addToSubjectOfSplitting(Glyph $glyph);

    abstract protected function breakSubjectOfSplittingIncraseTranslation($verticalTranslation);

    protected function postFormat()
    {
    }
}