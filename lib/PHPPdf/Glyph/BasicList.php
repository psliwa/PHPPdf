<?php

namespace PHPPdf\Glyph;

use PHPPdf\Document;

use PHPPdf\Util\DrawingTask;

class BasicList extends Container
{
    const TYPE_CIRCLE = '•';
    const TYPE_SQUARE = '▫';
    const TYPE_DISC = 'ο';
    const TYPE_NONE = '';
    
    const POSITION_INSIDE = 'inside';
    const POSITION_OUTSIDE = 'outside';
    
    public function initialize()
    {
        parent::initialize();
        
        $this->addAttribute('type', self::TYPE_CIRCLE);
        $this->addAttribute('image');
        $this->addAttribute('position', self::POSITION_OUTSIDE);
    }
    
    protected function doDraw(Document $document)
    {
        parent::doDraw($document);
        
        $glyph = $this;
        $task = new DrawingTask(function() use($glyph){
            $gc = $glyph->getGraphicsContext();
            
            $font = $glyph->getAttribute('font-type');
            
            $type = $glyph->getRecurseAttribute('type');
            $fontSize = $glyph->getRecurseAttribute('font-size');
            $widthOfTypeChar = $font->getCharsWidth(array(ord($type)), $fontSize);
            $encoding = $glyph->getPage()->getAttribute('encoding');
            $position = $glyph->getAttribute('position');
            
            foreach($glyph->getChildren() as $child)
            {
                $firstPoint = $child->getFirstPoint();
                $x = $firstPoint->getX() + ($position == BasicList::POSITION_OUTSIDE ? -$widthOfTypeChar : $widthOfTypeChar);
                $y = $firstPoint->getY() - $fontSize;
                $gc->drawText($glyph->getAttribute('type'), $x, $y, $encoding);
            }
        });
        
        $this->addDrawingTask($task);
    }
}