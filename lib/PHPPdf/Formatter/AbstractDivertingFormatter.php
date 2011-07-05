<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Formatter;

use PHPPdf\Document,
    PHPPdf\Glyph\Glyph;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
abstract class AbstractDivertingFormatter extends BaseFormatter
{
    protected $glyph;
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
            $pageYCoordEnd = $this->getPageYCoordEnd();
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
            
            $pageYCoordEnd = $this->getPageYCoordEnd();
        }
        while($childMayBeSplitted);
    }
    
    protected function getPageYCoordEnd()
    {
        return $this->glyph->getPage()->getDiagonalPoint()->getY();
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
        $end = $this->getPageYCoordEnd();
        $splitLine = $glyphYCoordStart - $end;
        $splittedGlyph = $glyph->split($splitLine);

        $gapBeetwenBottomOfOriginalGlyphAndEndOfPage = 0;

        if($splittedGlyph)
        {           
            $gapBeetwenBottomOfOriginalGlyphAndEndOfPage = $glyph->getDiagonalPoint()->getY() - $end;

            $gap = $originalHeight - (($glyph->getFirstPoint()->getY() - $glyph->getDiagonalPoint()->getY()) + ($splittedGlyph->getFirstPoint()->getY() - $splittedGlyph->getDiagonalPoint()->getY()));
            $this->totalVerticalTranslation += $gap;
            $glyph->resize(0, $gap);

            $glyphYCoordStart = $splittedGlyph->getFirstPoint()->getY();
            $this->addToSubjectOfSplitting($glyph);
            $glyph = $splittedGlyph;
        }

        $this->breakSubjectOfSplittingIncraseTranslation($glyph, $glyphYCoordStart, $gapBeetwenBottomOfOriginalGlyphAndEndOfPage);

        return $glyph;
    }

    private function getChildYCoordOfFirstPoint(Glyph $glyph)
    {
        $yCoordOfFirstPoint = $glyph->getFirstPoint()->getY();

        return $yCoordOfFirstPoint;
    }

    abstract protected function addToSubjectOfSplitting(Glyph $glyph);

    protected function postFormat()
    {
    }
}