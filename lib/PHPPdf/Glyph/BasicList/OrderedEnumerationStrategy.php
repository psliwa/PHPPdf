<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph\BasicList;

use PHPPdf\Glyph\GraphicsContext;

use PHPPdf\Font\Font;

use PHPPdf\Glyph\BasicList;

class OrderedEnumerationStrategy extends TextEnumerationStrategy
{
    private $pattern = '%s.';
    
    protected function assembleEnumerationText(BasicList $list, $number)
    {
        return sprintf($this->pattern, $number);
    }

    public function setPattern($pattern)
    {
        $this->pattern = $pattern;
    }

    public function getWidthOfTheBiggestPosibleEnumerationElement(BasicList $list)
    {
        $enumerationText = $this->assembleEnumerationText($list, count($list->getChildren()));
        return $this->getWidthOfText($enumerationText, $list->getFontType(true), $list->getFontSizeRecursively());
    }
}