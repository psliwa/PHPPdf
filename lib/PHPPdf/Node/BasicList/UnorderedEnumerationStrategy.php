<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Node\BasicList;

use PHPPdf\Document;

use PHPPdf\Engine\GraphicsContext,
    PHPPdf\Node\BasicList;

/**
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