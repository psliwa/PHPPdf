<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph\Paragraph;

use PHPPdf\Glyph\Paragraph;

use PHPPdf\Util\Point;

use PHPPdf\Document,
    PHPPdf\Glyph\Drawable;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Line implements Drawable
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
    
    public function getParts()
    {
        return $this->parts;
    }
    
    public function getDrawingTasks(Document $document)
    {
        $tasks = array();
        
        foreach($this->parts as $part)
        {
            $partTasks = $part->getDrawingTasks($document);
            foreach($partTasks as $task)
            {
                $tasks[] = $task;
            }
        }
        
        return $tasks;
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