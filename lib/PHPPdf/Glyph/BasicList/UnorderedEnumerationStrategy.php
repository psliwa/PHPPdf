<?php

namespace PHPPdf\Glyph\BasicList;

use PHPPdf\Font\Font,
    PHPPdf\Glyph\BasicList;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class UnorderedEnumerationStrategy implements EnumerationStrategy
{
    private $list;
    private $chars;
    private $font;
    
    private $widthOfChars = null;
    
    public function __construct(BasicList $list, Font $font, array $chars)
    {
        $this->list = $list;
        $this->font = $font;
        $this->chars = $chars;
    }
    
	public function getCurrentEnumerationText()
    {
        return implode('', $this->chars);
    }
    
	public function getWidthOfCurrentEnumerationChars()
    {
        return $this->getWidthOfChars();        
    }
    
    private function getWidthOfChars()
    {
        if($this->widthOfChars === null)
        {
            $fontSize = $this->list->getRecurseAttribute('font-size');
            $charCodes = $this->getCharCodes();
            $this->widthOfChars = $this->font->getCharsWidth($charCodes, $fontSize);
        }

        return $this->widthOfChars;
    }
    
    private function getCharCodes()
    {
        $charCodes = array();
        foreach($this->chars as $char)
        {
            $charCodes[] = ord($char);
        }
        
        return $charCodes;
    }

	public function getWidthOfLastEnumerationChars()
    {
        return $this->getWidthOfChars();
    }

	public function next()
    {
    }
}