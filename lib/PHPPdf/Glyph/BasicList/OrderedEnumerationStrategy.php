<?php

namespace PHPPdf\Glyph\BasicList;

use PHPPdf\Font\Font;

use PHPPdf\Glyph\BasicList;

class OrderedEnumerationStrategy implements EnumerationStrategy
{
    private $list;
    private $font;
    
    private $current = 1;
    
    public function __construct(BasicList $list, Font $font)
    {
        $this->list = $list;
        $this->font = $font;
    }
    
	public function getCurrentEnumerationText()
    {
        return $this->assembleEnumerationText($this->current);
    }
    
    private function assembleEnumerationText($number)
    {
        return $number.'.';
    }

	public function getWidthOfCurrentEnumerationChars()
    {
        $text = $this->getCurrentEnumerationText();
        
        return $this->getWidthOfText($text);        
    }

	public function getWidthOfLastEnumerationChars()
    {
        $numberOfChildren = count($this->list->getChildren());

        $text = $this->assembleEnumerationText($numberOfChildren);
        
        return $this->getWidthOfText($text);
    }
    
    private function getWidthOfText($text)
    {
        $fontSize = $this->list->getRecurseAttribute('font-size');
        
        $charCodes = array();
        foreach(str_split($text) as $char)
        {
            $charCodes[] = ord($char);
        }

        return $this->font->getCharsWidth($charCodes, $fontSize);
    }

	public function next()
    {
        $this->current++;
    }
}