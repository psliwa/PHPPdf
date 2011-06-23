<?php

namespace PHPPdf\Glyph\BasicList;

use PHPPdf\Glyph\GraphicsContext,
    PHPPdf\Font\Font,
    PHPPdf\Glyph\BasicList;

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
    
    public function getWidthOfTheBiggestPosibleEnumerationElement(BasicList $list)
    {
        return $this->getWidthOfText($list->getType(), $list->getFontType(true), $list->getRecurseAttribute('font-size'));
    }
}