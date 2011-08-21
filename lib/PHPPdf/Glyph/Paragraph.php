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
        
//        $this->mergeEnhancementAttributes('border', array('name' => 'border'));
    }
    
    public function getWidth()
    {
        return $this->getParent()->getWidth();
    }
    
    public function setWidth($width)
    {
        $this->getParent()->setWidth($width);
        return $this;
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
        $line->reorganizeParts();
    }
    
    public function getLines()
    {
        return $this->lines;
    }
    
    public function getDrawingTasks(Document $document)
    {
        foreach($this->lines as $line)
        {
            $line->applyHorizontalTranslation();
        }
        
        $tasks = array();
        
        foreach($this->getChildren() as $text)
        {
            $lineTasks = $text->getDrawingTasks($document);
            foreach($lineTasks as $task)
            {
                $tasks[] = $task;
            }
        }
        
        $tasks = array_merge($tasks, $this->getDrawingTasksFromEnhancements($document));
        
        return $tasks;
    }
    
    protected function doSplit($height)
    {
        $linesToMove = array();
        
        foreach($this->lines as $i => $line)
        {
            $lineEnd = $line->getYTranslation() + $line->getHeight();
            if($lineEnd > $height)
            {
                $linesToMove[] = $line;
                unset($this->lines[$i]);
            }
        }
        
        if(!$linesToMove)
        {
            return null;
        }
        
        $firstLineToMove = current($linesToMove);
        $yTranslation = $height - $firstLineToMove->getYTranslation();
        $height = $firstLineToMove->getYTranslation();
        
        $paragraphProduct = Glyph::doSplit($height);
        
        $paragraphProduct->removeAll();
        
        $replaceText = array();
        $textsToMove = array();
        
        foreach($firstLineToMove->getParts() as $part)
        {
            $text = $part->getText();
            
            $textHeight = $text->getFirstPoint()->getY() - ($this->getFirstPoint()->getY() - $height);
            
            $textProduct = $text->split($textHeight);
            
            if($textProduct)
            {
                $replaceText[spl_object_hash($text)] = $textProduct;
                $paragraphProduct->add($textProduct);
                $part->setText($textProduct);
            }
            else
            {
                $replaceText[spl_object_hash($text)] = $text;
                $textsToMove[spl_object_hash($text)] = $text;
            }
        }        
        
        foreach($linesToMove as $line)
        {
            $line->setYTranslation($line->getYTranslation() - $height);
            $line->setParagraph($paragraphProduct);
            $paragraphProduct->addLine($line);
        }
        
        array_shift($linesToMove);
        
        foreach($linesToMove as $line)
        {
            foreach($line->getParts() as $part)
            {
                $text = $part->getText();
                $hash = spl_object_hash($text);
                
                if(isset($replaceText[$hash]))
                {
                    $part->setText($replaceText[$hash]);
                }
                else
                {
                    $textsToMove[$hash] = $text;
                }
            }
        }
        
        foreach($textsToMove as $text)
        {
            $this->remove($text);
            $paragraphProduct->add($text);
        }
        
        $paragraphProduct->translate(0, $yTranslation);
        $this->resize(0, $yTranslation);

        return $paragraphProduct;
    }
    
    public function copy()
    {
        $copy = parent::copy();
        $copy->lines = array();
        
        return $copy;
    }
}