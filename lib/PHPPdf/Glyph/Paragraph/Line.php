<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph\Paragraph;

use PHPPdf\Glyph\Glyph;

use PHPPdf\Glyph\Paragraph;

use PHPPdf\Util\Point;

use PHPPdf\Document,
    PHPPdf\Glyph\Drawable;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Line
{
    private $parts = array();
    private $yTranslation;
    private $xTranslation;
    private $paragraph;
    
    public function __construct(Paragraph $paragraph, $xTranslation, $yTranslation)
    {
        $this->xTranslation = $xTranslation;
        $this->yTranslation = $yTranslation;
        $this->paragraph = $paragraph;
    }
    
    public function addPart(LinePart $linePart)
    {
        $linePart->setLine($this);
        $this->parts[] = $linePart;
    }
    
    public function addParts(array $parts)
    {
        foreach($parts as $part)
        {
            $this->addPart($part);
        }
    }
    
    public function setYTranslation($translation)
    {
        $this->yTranslation = $translation;
    }
    
    public function setXTranslation($translation)
    {
        $this->xTranslation = $translation;
    }
    
    public function getParts()
    {
        return $this->parts;
    }
    
    public function applyHorizontalTranslation()
    {
         $this->setXTranslation($this->getHorizontalTranslation());
    }
    
    private function getHorizontalTranslation()
    {
        $align = $this->paragraph->getRecurseAttribute('text-align');
        switch($align)
        {
            case Glyph::ALIGN_LEFT:
                return 0;
            case Glyph::ALIGN_RIGHT:
                return  $this->getRealWidth() - $this->getTotalWidth();
            case Glyph::ALIGN_CENTER:
                return ($this->getRealWidth() - $this->getTotalWidth())/2;
            default:
                throw new \InvalidArgumentException(sprintf('Unsupported align type "%s".', $align));
        }
    }
    
    private function getRealWidth()
    {
        return $this->paragraph->getWidth() - $this->paragraph->getParentPaddingLeft() - $this->paragraph->getParentPaddingRight();
    }
    
    private function getTotalWidth()
    {
        $width = 0;
        foreach($this->parts as $part)
        {
            $width += $part->getWidth();
        }
        
        return $width;
    }
    
    /**
     * @return PHPPdf\Util\Point
     */
    public function getFirstPoint()
    {
        return $this->paragraph->getFirstPoint()->translate($this->xTranslation, $this->yTranslation);
    }
    
    public function getHeight()
    {
        $height = 0;
        
        foreach($this->parts as $part)
        {
            $height = max($height, $part->getHeight());
        }
        
        return $height;
    }
}