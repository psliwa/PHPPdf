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
class Paragraph extends Container
{
    public function initialize()
    {
        parent::initialize();
        $this->setAttribute('text-align', null);
    }
    
    public function getWidth()
    {
        return $this->getParent()->getWidth();
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
}