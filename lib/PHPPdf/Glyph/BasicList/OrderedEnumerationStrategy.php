<?php

namespace PHPPdf\Glyph\BasicList;

use PHPPdf\Glyph\GraphicsContext;

use PHPPdf\Font\Font;

use PHPPdf\Glyph\BasicList;

class OrderedEnumerationStrategy extends TextEnumerationStrategy
{      
    private $initialIndex;
    
    public function __construct($initialIndex = 1)
    {
        $this->setInitialIndex($initialIndex);
    }
    
    public function setInitialIndex($initialIndex)
    {
        $this->initialIndex = $initialIndex;
    }
    
    protected function assembleEnumerationText(BasicList $list, $number)
    {
        return ($number+$this->initialIndex).'.';
    }
    
    public function getWidthOfTheBiggestPosibleEnumerationElement(BasicList $list)
    {
        $enumerationText = $this->assembleEnumerationText($list, count($list->getChildren()));
        return $this->getWidthOfText($enumerationText, $list->getFontType(true), $list->getRecurseAttribute('font-size'));
    }
}