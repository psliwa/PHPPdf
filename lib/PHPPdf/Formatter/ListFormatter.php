<?php

namespace PHPPdf\Formatter;

use PHPPdf\Document,
    PHPPdf\Glyph\Glyph,
    PHPPdf\Glyph\BasicList;

class ListFormatter extends BaseFormatter
{
    public function format(Glyph $glyph, Document $document)
    {
        $position = $glyph->getAttribute('position');
        
        if($position === BasicList::POSITION_INSIDE)
        {
            $widthOfEnumerationChar = $glyph->getEnumerationStrategy()->getWidthOfLastEnumerationChars();
            
            foreach($glyph->getChildren() as $child)
            {
                $marginLeft = $widthOfEnumerationChar + $child->getMarginLeft();
                $child->setAttribute('margin-left', $marginLeft);
            }
        }
    }
}