<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Formatter;

use PHPPdf\Document,
    PHPPdf\Glyph\Glyph,
    PHPPdf\Glyph\BasicList;

class ListFormatter extends BaseFormatter
{
    public function format(Glyph $glyph, Document $document)
    {
        $position = $glyph->getAttribute('position');
        
        $glyph->assignEnumerationStrategyFromFactory();
        
        if($position === BasicList::POSITION_INSIDE)
        {
            $widthOfEnumerationChar = $glyph->getEnumerationStrategy()->getWidthOfTheBiggestPosibleEnumerationElement($document, $glyph);
            
            foreach($glyph->getChildren() as $child)
            {
                $marginLeft = $widthOfEnumerationChar + $child->getMarginLeft();
                $child->setAttribute('margin-left', $marginLeft);
            }
        }
    }
}