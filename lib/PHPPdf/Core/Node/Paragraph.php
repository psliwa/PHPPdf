<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node;

use PHPPdf\Core\DrawingTaskHeap;

use PHPPdf\Core\Document,
    PHPPdf\Core\Node\Paragraph\Line;

/**
 * Paragraph element
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
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
        if($this->getParent())
        {
            //paragraph hasn't his own width, his width is equal to parent's one
            return $this->getParent()->getWidth();
        }
        
        return 0;
    }
    
    public function setWidth($width)
    {
        $parent = $this->getParent();
        
        //paragraph hasn't his own width, his width is equal to parent's one
        if($parent && !$parent instanceof Page)
        {
            $parent->setWidth($width);
        }
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
    
    public function getWidthWithoutPaddings()
    {
        return $this->getParent()->getWidthWithoutPaddings();
    }

    public function add(Node $text)
    {
        $previousText = $this->getLastChild();
        
        parent::add($text);

        $text->setText(preg_replace('/[ \t]+/', ' ', $text->getText()));

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
    
    public function collectOrderedDrawingTasks(Document $document, DrawingTaskHeap $tasks)
    {
        $lastIndex = count($this->lines) - 1;
        foreach($this->lines as $i => $line)
        {
            $line->format($i != $lastIndex);
        }
        
        foreach($this->getChildren() as $text)
        {
            $text->collectOrderedDrawingTasks($document, $tasks);
        }
        
        $this->getDrawingTasksFromComplexAttributes($document, $tasks);
        
        if($this->getAttribute('dump'))
        {
            $tasks->insert($this->createDumpTask());
        }

        return $tasks;
    }
    
    protected function doBreakAt($height)
    {
        $linesToMove = array();
        $numberOfLines = count($this->lines);
        foreach($this->lines as $i => $line)
        {
            $lineEnd = $line->getYTranslation() + $line->getHeight();
            if($lineEnd > $height)
            {
                $linesToMove[] = $line;
                unset($this->lines[$i]);
            }
        }
        
        if(!$linesToMove || count($linesToMove) == $numberOfLines)
        {
            return null;
        }
        
        $firstLineToMove = current($linesToMove);
        $yTranslation = $height - $firstLineToMove->getYTranslation();
        $height = $firstLineToMove->getYTranslation();
        
        $paragraphProduct = Node::doBreakAt($height);
        
        $paragraphProduct->removeAll();
        
        $replaceText = array();
        $textsToMove = array();
        
        foreach($firstLineToMove->getParts() as $part)
        {
            $text = $part->getText();
            
            $textHeight = $text->getFirstPoint()->getY() - ($this->getFirstPoint()->getY() - $height);
            
            $textProduct = $text->breakAt($textHeight);
            
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
        $paragraphProduct->resize(0, -$yTranslation);
        $paragraphProduct->resize(0, $yTranslation);

        return $paragraphProduct;
    }
    
    public function copy()
    {
        $copy = parent::copy();
        $copy->lines = array();
        
        return $copy;
    }
    
    public function getMinWidth()
    {
        $minWidth = 0;
        
        foreach($this->lines as $line)
        {
            $minWidth = max($line->getTotalWidth(), $minWidth);
        }
        
        return $minWidth;
    }
    
    public function flush()
    {
        foreach($this->lines as $line)
        {
            $line->flush();
        }
        
        $this->lines = array();
        
        parent::flush();
    }
    
    public function resize($x, $y)
    {
    }
}