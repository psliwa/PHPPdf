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
class VerticalAlignFormatter extends BaseFormatter
{
    public function format(Glyph $glyph, Document $document)
    {
        $verticalAlign = $glyph->getRecurseAttribute('vertical-align');
        
        if($verticalAlign == Glyph::VERTICAL_ALIGN_TOP || $verticalAlign == null)
        {
            return;
        }
        
        $this->processVerticalAlign($glyph, $verticalAlign);
    }
    
    private function processVerticalAlign(Glyph $glyph, $verticalAlign)
    {
        $minYCoord = $this->getMinimumYCoordOfChildren($glyph);

        $translation = $this->getVerticalTranslation($glyph, $minYCoord, $verticalAlign);

        $this->verticalTranslateOfGlyphs($glyph->getChildren(), $translation);
    }
    
    private function sortChildren($glyph)
    {
        $children = $glyph->getChildren();
        
        usort($children, function($firstChild, $secondChild){
            if($firstChild->getDiagonalPoint()->getY() < $secondChild->getDiagonalPoint()->getY())
            {
                return 1;
            }
            
            if($firstChild->getDiagonalPoint()->getY() == $secondChild->getDiagonalPoint()->getY())
            {
                return 0;
            }
            
            return -1;
        });
        
        return $children;
    }
    
    private function getMinimumYCoordOfChildren(Glyph $glyph)
    {
        $minYCoord = $glyph->getFirstPoint()->getY();

        foreach($glyph->getChildren() as $child)
        {
            $minYCoord = min($minYCoord, $child->getDiagonalPoint()->getY());
        }
        
        return $minYCoord;
    }
    
    private function getVerticalTranslation(Glyph $glyph, $minYCoord, $verticalAlign)
    {
        $difference = $minYCoord - $glyph->getDiagonalPoint()->getY();
        
        if($verticalAlign == Glyph::VERTICAL_ALIGN_MIDDLE)
        {
            $difference /= 2;
        }
        
        return $difference;
    }
    
    private function verticalTranslateOfGlyphs(array $glyphs, $verticalTranslation)
    {
        foreach($glyphs as $glyph)
        {
            $glyph->translate(0, $verticalTranslation);
        }
    }
}