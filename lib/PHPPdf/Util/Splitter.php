<?php

namespace PHPPdf\Util;

use PHPPdf\Glyph\Glyph;

/**
 * TODO: unit tests, refactoring and make more generic
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Splitter
{
    private $glyph;
    private $totalTranslation = 0;

    public function __construct(Glyph $glyph)
    {
        $this->glyph = $glyph;
    }

    public function split()
    {
        $this->splitChildrenIntoPages();
    }


    private function splitChildrenIntoPages()
    {
        foreach($this->glyph->getChildren() as $child)
        {
            $this->splitChildIfNecessary($child);
        }
    }


    private function splitChildIfNecessary(Glyph $glyph)
    {
        $childHasBeenSplitted = false;
        $childMayBeSplitted = true;
        $glyph->translate(0, -$this->totalTranslation);

        if($glyph->getPageBreak())
        {
            $pageYCoordEnd = $glyph->getDiagonalPoint()->getY() + 1;
        }
        else
        {
            $pageYCoordEnd = $this->glyph->getDiagonalPoint()->getY();
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
                    $this->glyph->getCurrentPage()->add($glyph);
                }

                $childMayBeSplitted = false;
            }
        }
        while($childMayBeSplitted);
    }

    private function shouldBeSplited(Glyph $glyph, $pageYCoordEnd)
    {
        $yEnd = $glyph->getDiagonalPoint()->getY();

        return ($yEnd < $pageYCoordEnd);
    }

    /**
     * @return Glyph Product of splitting
     */
    private function splitChildAndGetProductOfSplitting(Glyph $glyph)
    {
        $originalHeight = $glyph->getFirstPoint()->getY() - $glyph->getDiagonalPoint()->getY();
        $glyphYCoordStart = $this->getChildYCoordOfFirstPoint($glyph);
        $splitLine = $glyphYCoordStart - $this->glyph->getDiagonalPoint()->getY();
        $splittedGlyph = $glyph->split($splitLine);

        $heightAfterSplit = $glyph->getFirstPoint()->getY() - $glyph->getDiagonalPoint()->getY();

        $gapBeetwenBottomOfOriginalGlyphAndEndOfPage = 0;

        if($splittedGlyph)
        {
            $gapBeetwenBottomOfOriginalGlyphAndEndOfPage = $glyph->getDiagonalPoint()->getY() - $this->glyph->getDiagonalPoint()->getY();
            $glyphYCoordStart = $splittedGlyph->getFirstPoint()->getY();
            $this->glyph->getCurrentPage()->add($glyph);
            $heightAfterSplit += $splittedGlyph->getFirstPoint()->getY() - $splittedGlyph->getDiagonalPoint()->getY();
            $glyph = $splittedGlyph;
        }

        $translation = $this->getGlyphTranslation($glyph, $glyphYCoordStart);
        $this->glyph->createNextPage();

        $this->totalTranslation += $translation - $gapBeetwenBottomOfOriginalGlyphAndEndOfPage;

        $this->addChildrenToCurrentPageAndTranslate($glyph, $translation);

        return $glyph;
    }

    private function getChildYCoordOfFirstPoint(Glyph $glyph)
    {
        $yCoordOfFirstPoint = $glyph->getFirstPoint()->getY();

        return $yCoordOfFirstPoint;
    }

    private function getGlyphTranslation(Glyph $glyph, $glyphYCoordStart)
    {
        $translation = $this->glyph->getHeight() + $this->glyph->getMarginBottom() - $glyphYCoordStart;

        return $translation;
    }

    private function addChildrenToCurrentPageAndTranslate(Glyph $glyph, $translation)
    {
        $this->glyph->getCurrentPage()->add($glyph);
        $glyph->translate(0, -$translation);
    }
}