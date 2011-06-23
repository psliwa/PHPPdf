<?php

namespace PHPPdf\Glyph\BasicList;

use PHPPdf\Glyph\GraphicsContext,
    PHPPdf\Font\Font,
    PHPPdf\Glyph\BasicList;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
abstract class TextEnumerationStrategy implements EnumerationStrategy
{
    private $widthOfTextCache = array();
    private $initialIndex = 1;
    
    public function drawEnumeration(BasicList $list, GraphicsContext $gc, $elementIndex)
    {
        $child = $list->getChild($elementIndex);
        
        $point = $child->getFirstPoint();
        
        $enumerationText = $this->assembleEnumerationText($list, $elementIndex);

        $fontSize = $list->getRecurseAttribute('font-size');
        $font = $list->getFontType(true);
        
        $xTranslation = 0;
        
        if($list->getAttribute('position') == BasicList::POSITION_OUTSIDE)
        {
            $widthOfEnumerationText = $this->getWidthOfText($enumerationText, $font, $fontSize);
            $xTranslation -= $widthOfEnumerationText;
        }
        
        $xCoord = $point->getX() - $child->getMarginLeft() + $xTranslation;
        $yCoord = $point->getY() - $fontSize;
        $encoding = $list->getEncoding();
        
        $gc->drawText($enumerationText, $xCoord, $yCoord, $encoding);
    }
       
    abstract protected function assembleEnumerationText(BasicList $list, $number);
    
    protected function getWidthOfText($text, Font $font, $fontSize)
    {
        if(!isset($this->widthOfTextCache[$text]))
        {
            $charCodes = array();
            foreach($this->splitTextIntoChars($text) as $char)
            {
                $charCodes[] = ord($char);
            }
    
            $this->widthOfTextCache[$text] = $font->getCharsWidth($charCodes, $fontSize);
        }

        return $this->widthOfTextCache[$text];        
    }
    
    protected function splitTextIntoChars($text)
    {
        return str_split($text);
    }
    
    public function reset()
    {
        $this->widthOfTextCache = array();
    }
	public function getInitialIndex()
    {
        return $this->initialIndex;
        
    }

	public function setInitialIndex($index)
    {
        $this->initialIndex = $index;
    }
}