<?php

namespace PHPPdf\Glyph;

use PHPPdf\Document;

use PHPPdf\Util\DrawingTask;

class BasicList extends Container
{
    const TYPE_CIRCLE = '•';
    const TYPE_SQUARE = '▪';
    const TYPE_DISC = '◦';
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
    
    protected static function initializeType()
    {
        parent::initializeType();
        
        static::setAttributeSetters(array('type' => 'setType'));
    }
    
    public function setType($type)
    {
        $const = sprintf('%s::TYPE_%s', __CLASS__, strtoupper($type));
        
        if(defined($const))
        {
            $type = constant($const);
        }
        
        $this->setAttributeDirectly('type', $type);
    }
    
    protected function doDraw(Document $document)
    {
        parent::doDraw($document);
        
        $glyph = $this;
        $task = new DrawingTask(function() use($glyph){
            $gc = $glyph->getGraphicsContext();
            
            $fontSize = $glyph->getRecurseAttribute('font-size');
            $widthOfTypeChar = $glyph->getWidthOfEnumerationChar();
            $encoding = $glyph->getPage()->getAttribute('encoding');
            $position = $glyph->getAttribute('position');
            
            foreach($glyph->getChildren() as $child)
            {
                $firstPoint = $child->getFirstPoint();
                $x = $firstPoint->getX() + ($position == BasicList::POSITION_OUTSIDE ? -$widthOfTypeChar : 0) - $child->getMarginLeft();
                $y = $firstPoint->getY() - $fontSize;
                $gc->drawText($glyph->getAttribute('type'), $x, $y, $encoding);
            }
        });
        
        $this->addDrawingTask($task);
    }
    
    public function getWidthOfEnumerationChar()
    {
        $type = $this->getAttribute('type');
        $font = $this->getRecurseAttribute('font-type');
        $fontSize = $this->getRecurseAttribute('font-size');
        
        return $font->getCharsWidth(array(ord($type)), $fontSize);
    }
}