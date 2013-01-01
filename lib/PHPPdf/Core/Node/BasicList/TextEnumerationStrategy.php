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
 * Enumeration strategy that draws text as enumeration element
 * 
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
        
        if($list->getAttribute('list-position') == BasicList::LIST_POSITION_OUTSIDE)
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
            $gc->setFillColor($color);
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
            $this->widthOfTextCache[$text] = $font->getWidthOfText($text, $fontSize);
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