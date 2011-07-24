<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
use PHPPdf\Document;

use PHPPdf\Glyph\Paragraph\Line;

class Paragraph extends Container
{
    private $lines = array();
    
    public function initialize()
    {
        parent::initialize();
        $this->setAttribute('text-align', null);
    }
    
    public function getWidth()
    {
        return $this->getParent()->getWidth();
    }
    
    public function getParentPaddingLeft()
    {
        return $this->getParent()->getPaddingLeft();
    }
    
    public function getParentPaddingRight()
    {
        return $this->getParent()->getPaddingRight();
    }

    public function add(Glyph $text)
    {
        $previousText = $this->getLastChild();
        
        parent::add($text);

        $text->setText(preg_replace('/\s+/', ' ', $text->getText()));

        if(!$previousText || $this->startsWithWhiteChars($text) && $this->endsWithWhiteChars($previousText))
        {
            $text->setText(ltrim($text->getText()));
        }        
        
        return $this;
    }
    
    private function getLastChild()
    {
        $lastIndex = count($this->getChildren()) - 1;
        if($lastIndex >= 0)
        {
            return $this->getChild($lastIndex);
        }
        
        return null;
    }
    
    private function endsWithWhiteChars(Text $text)
    {
        return rtrim($text->getText()) != $text->getText();
    }
    
    private function startsWithWhiteChars(Text $text)
    {
        return ltrim($text->getText()) != $text->getText();
    }
    
    public function addLine(Line $line)
    {
        $this->lines[] = $line;
    }
    
    public function getLines()
    {
        return $this->lines;
    }
    
    public function getDrawingTasks(Document $document)
    {
        $tasks = array();
        
        foreach($this->lines as $line)
        {
            $lineTasks = $line->getDrawingTasks($document);
            foreach($lineTasks as $task)
            {
                $tasks[] = $task;
            }
        }
        
        return $tasks;
    }
}