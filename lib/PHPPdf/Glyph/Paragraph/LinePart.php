<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph\Paragraph;

use PHPPdf\Util\DrawingTask,
    PHPPdf\Document,
    PHPPdf\Util\Point,
    PHPPdf\Glyph\Drawable,
    PHPPdf\Glyph\Text;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class LinePart implements Drawable
{
    private $text;
    private $words;
    private $line;
    private $xTranslation;
    
    public function __construct($words, $xTranslation, Text $text)
    {
        $this->words = (string) $words;
        $text->addLinePart($this);
        $this->text = $text;
        $this->xTranslation = $xTranslation;
    }
    
    public function setLine(Line $line)
    {
        $this->line = $line;
    }
    
    public function getDrawingTasks(Document $document)
    {
        return array(new DrawingTask(function(Text $text, $xTranslation, $point, $words) {
            $gc = $text->getGraphicsContext();
            $gc->saveGS();
            $fontSize = $text->getFontSize();
            
            $gc->setFont($text->getFont(), $fontSize);
            $color = $text->getAttribute('color');
            
            if($color)
            {
                $gc->setFillColor($color);
            }
            
            $gc->drawText($words, $point->getX() + $xTranslation, $point->getY() - $fontSize, $text->getEncoding());
            
            $gc->restoreGS();          
        }, array($this->text, $this->xTranslation, $this->line->getFirstPoint(), $this->words)));
    }
    
    public function getFirstPoint()
    {
        return $this->line->getFirstPoint()->translate($this->xTranslation, 0);
    }
    
    public function getHeight()
    {
        return $this->text->getRecurseAttribute('line-height');
    }
    
    public function getText()
    {
        return $this->text;
    }
}