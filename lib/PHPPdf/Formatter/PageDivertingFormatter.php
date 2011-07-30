<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Formatter;

use PHPPdf\Glyph\Glyph,
    PHPPdf\Document;

/**
 * TODO: refactoring and rename
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class PageDivertingFormatter extends BaseFormatter
{
    protected $glyph;
    protected $totalVerticalTranslation = 0;
    
    public function format(Glyph $glyph, Document $document)
    {
        $columnFormatter = new ColumnDivertingFormatter();

        $this->glyph = $glyph;
        $this->totalVerticalTranslation = 0;

        $children = $this->glyph->getChildren();
        foreach($this->glyph->getChildren() as $child)
        {
            $child->translate(0, -$this->totalVerticalTranslation);
            
            $columnFormatter->format($child, $document);   

            $verticalTranslation = $columnFormatter->getLastVerticalTranslation();
            
            $this->splitChildIfNecessary($child);
            
            $this->totalVerticalTranslation += -$verticalTranslation;
        }
    }
    
    private function splitChildIfNecessary(Glyph $glyph)
    {
        $childHasBeenSplitted = false;
        $childMayBeSplitted = true;
        
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
    
    private function shouldParentBeAutomaticallyBroken(Glyph $glyph)
    {
        return $glyph->getAttribute('page-break');
    }
    
    private function getPageYCoordEnd()
    {
        return $this->glyph->getPage()->getDiagonalPoint()->getY();
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

    private function addToSubjectOfSplitting(Glyph $glyph)
    {
        $this->getSubjectOfSplitting()->getCurrentPage()->add($glyph);
    }

    private function breakSubjectOfSplittingIncraseTranslation(Glyph $glyph, $glyphYCoordStart, $gapBeetwenBottomOfOriginalGlyphAndEndOfPage)
    {
        $translation = $this->glyph->getPage()->getHeight() + $this->glyph->getPage()->getMarginBottom() - $glyphYCoordStart;
        $verticalTranslation = $translation - $gapBeetwenBottomOfOriginalGlyphAndEndOfPage;
        
        $this->getSubjectOfSplitting()->createNextPage();
        $this->totalVerticalTranslation += $verticalTranslation;
        
        $this->getSubjectOfSplitting()->getCurrentPage()->add($glyph);
        $glyph->translate(0, -$translation);
    }
    
    /**
     * @return Glyph
     */
    private function getSubjectOfSplitting()
    {
        return $this->glyph;
    }
    
    private function shouldBeSplited(Glyph $glyph, $pageYCoordEnd)
    {
        $yEnd = $glyph->getDiagonalPoint()->getY();

        return ($yEnd < $pageYCoordEnd);
    }

    private function getChildYCoordOfFirstPoint(Glyph $glyph)
    {
        $yCoordOfFirstPoint = $glyph->getFirstPoint()->getY();

        return $yCoordOfFirstPoint;
    }
}