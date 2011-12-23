<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node\Paragraph;

use PHPPdf\Exception\InvalidArgumentException;
use PHPPdf\Core\Node\Node;
use PHPPdf\Core\Node\Paragraph;
use PHPPdf\Core\Point;
use PHPPdf\Core\Document;

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
    
    public function getYTranslation()
    {
        return $this->yTranslation;
    }
    
    public function setXTranslation($translation)
    {
        $this->xTranslation = $translation;
    }
    
    public function getXTranslation()
    {
        return $this->xTranslation;
    }
    
    public function getParts()
    {
        return $this->parts;
    }
    
    public function setParagraph($paragraph)
    {
        $this->paragraph = $paragraph;
    }
    
    private function getHorizontalTranslation()
    {
        $align = $this->paragraph->getRecurseAttribute('text-align');
        switch($align)
        {
            case Node::ALIGN_LEFT:
            case Node::ALIGN_JUSTIFY:
                return 0;
            case Node::ALIGN_RIGHT:
                return  $this->getRealWidth() - $this->getTotalWidth();
            case Node::ALIGN_CENTER:
                return ($this->getRealWidth() - $this->getTotalWidth())/2;
            default:
                throw new InvalidArgumentException(sprintf('Unsupported align type "%s".', $align));
        }
    }
    
    private function getRealWidth()
    {
        return $this->paragraph->getWidth() - $this->paragraph->getParentPaddingLeft() - $this->paragraph->getParentPaddingRight();
    }
    
    public function getTotalWidth()
    {
        $width = 0;
        foreach($this->parts as $part)
        {
            $width += $part->getWidth();
        }
        
        return $width;
    }
    
    /**
     * @return PHPPdf\Core\Point
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
    
    public function format($formatJustify = true)
    {
        $this->setXTranslation($this->getHorizontalTranslation());
        
        if(!$formatJustify || $this->paragraph->getRecurseAttribute('text-align') !== Node::ALIGN_JUSTIFY)
        {
            return;
        }

        $numberOfSpaces = $this->getNumberOfWords() - 1;
        
        $wordSpacing = $numberOfSpaces ? ($this->getRealWidth() - $this->getTotalWidth()) / $numberOfSpaces : null;

        $wordSpacingSum = 0;
        foreach($this->parts as $part)
        {
            $part->setWordSpacing($wordSpacing);
            $part->horizontalTranslate($wordSpacingSum);
            $wordSpacingSum += $part->getWordSpacingSum();
        }
    }
    
    private function getNumberOfWords()
    {
        $count = 0;
        
        foreach($this->parts as $part)
        {
            $count += $part->getNumberOfWords();
        }
        
        return $count;
    }
    
    public function flush()
    {
        foreach($this->parts as $part)
        {
            $part->flush();
        }
        
        $this->parts = array();
        $this->paragraph = null;
    }
}