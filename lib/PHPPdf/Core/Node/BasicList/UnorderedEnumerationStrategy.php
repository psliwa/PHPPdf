<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node\BasicList;

use PHPPdf\Core\Document;

use PHPPdf\Core\Engine\GraphicsContext,
    PHPPdf\Core\Node\BasicList;

/**
 * Enumeration strategy that draws constant text as enumeration element
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class UnorderedEnumerationStrategy extends TextEnumerationStrategy
{
    protected function assembleEnumerationText(BasicList $list, $number)
    {
        return $list->getType();
    }
    
    protected function splitTextIntoChars($text)
    {
        return array($text);
    }
    
    public function getWidthOfTheBiggestPosibleEnumerationElement(Document $document, BasicList $list)
    {
        return $this->getWidthOfText($list->getType(), $list->getFont($document), $list->getFontSizeRecursively());
    }
}