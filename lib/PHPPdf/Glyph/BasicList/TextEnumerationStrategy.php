<?php

namespace PHPPdf\Glyph\BasicList;

use PHPPdf\Glyph\GraphicsContext,
    PHPPdf\Font\Font,
    PHPPdf\Glyph\BasicList;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
abstract class TextEnumerationStrategy extends AbstractEnumerationStrategy
{
    private $widthOfTextCache = array();
    private $initialIndex = 1;
    
    private $enumerationText = null;
        
    protected function getEnumerationElementTranslations(BasicList $list, $elementIndex)
    {
        $enumerationText = $this->assembleEnumerationText($list, $elementIndex);

        $fontSize = $list->getRecurseAttribute('font-size');
        $font = $list->getFontType(true);
        
        $xTranslation = 0;
        
        if($list->getAttribute('position') == BasicList::POSITION_OUTSIDE)
        {
            $widthOfEnumerationText = $this->getWidthOfText($enumerationText, $font, $fontSize);
            $xTranslation -= $widthOfEnumerationText;
        }
        
        $this->enumerationText = $enumerationText;
        
        return array($xTranslation, $fontSize);
    }
    
    protected function doDrawEnumeration(BasicList $list, GraphicsContext $gc, $xCoord, $yCoord)
    {
        $encoding = $list->getEncoding();
        
        $gc->drawText($this->enumerationText, $xCoord, $yCoord, $encoding);
        
        $this->enumerationText = null;
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
}