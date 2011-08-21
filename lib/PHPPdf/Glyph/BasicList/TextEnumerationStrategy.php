<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph\BasicList;

use PHPPdf\Document;

use PHPPdf\Engine\GraphicsContext,
    PHPPdf\Glyph\BasicList;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
abstract class TextEnumerationStrategy extends AbstractEnumerationStrategy
{
    private $widthOfTextCache = array();
    private $initialIndex = 1;
    
    private $enumerationText = null;
        
    protected function getEnumerationElementTranslations(Document $document, BasicList $list)
    {
        $enumerationText = $this->assembleEnumerationText($list, $this->visualIndex);

        $fontSize = $list->getFontSizeRecursively();
        $font = $list->getFont($document);
        
        $xTranslation = 0;
        
        if($list->getAttribute('position') == BasicList::POSITION_OUTSIDE)
        {
            $widthOfEnumerationText = $this->getWidthOfText($enumerationText, $font, $fontSize);
            $xTranslation -= $widthOfEnumerationText;
        }
        else
        {
            $widthOfEnumerationText = $this->getWidthOfTheBiggestPosibleEnumerationElement($document, $list) - $this->getWidthOfText($enumerationText, $font, $fontSize);
            $xTranslation += $widthOfEnumerationText;
        }
        
        $this->enumerationText = $enumerationText;
        
        return array($xTranslation, $fontSize);
    }
    
    protected function doDrawEnumeration(Document $document, BasicList $list, GraphicsContext $gc, $xCoord, $yCoord)
    {
        $encoding = $list->getEncoding();
        
        $gc->saveGS();
        
        $color = $list->getRecurseAttribute('color');
        
        if($color)
        {
            $gc->setLineColor($color);
        }
        
        $font = $list->getFont($document);
        $size = $list->getFontSizeRecursively();
        
        if($font && $size)
        {
            $gc->setFont($font, $size);
        }
        
        $gc->drawText($this->enumerationText, $xCoord, $yCoord, $encoding);
        
        $gc->restoreGS();
    }
       
    abstract protected function assembleEnumerationText(BasicList $list, $number);
    
    protected function getWidthOfText($text, $font, $fontSize)
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