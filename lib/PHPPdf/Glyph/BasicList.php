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
            foreach($glyph->getChildren() as $child)
            {
                $boundary = $child->getBoundary();
                $gc->drawText($glyph->getAttribute('type'), 0, 10, $glyph->getPage()->getAttribute('encoding'));
            }
        });
        
        $this->addDrawingTask($task);
    }
}