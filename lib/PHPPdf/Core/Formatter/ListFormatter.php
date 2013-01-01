<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Formatter;

use PHPPdf\Core\Document,
    PHPPdf\Core\Node\Node,
    PHPPdf\Core\Node\BasicList;

class ListFormatter extends BaseFormatter
{
    public function format(Node $node, Document $document)
    {
        $position = $node->getAttribute('list-position');
        
        $node->assignEnumerationStrategyFromFactory();
        
        if($position === BasicList::LIST_POSITION_INSIDE)
        {
            $widthOfEnumerationChar = $node->getEnumerationStrategy()->getWidthOfTheBiggestPosibleEnumerationElement($document, $node);
            
            foreach($node->getChildren() as $child)
            {
                $marginLeft = $widthOfEnumerationChar + $child->getMarginLeft();
                $child->setAttribute('margin-left', $marginLeft);
            }
        }
    }
}