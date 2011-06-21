<?php

namespace PHPPdf\Glyph;

use PHPPdf\Glyph\BasicList\OrderedEnumerationStrategy;

use PHPPdf\Glyph\BasicList\UnorderedEnumerationStrategy;

use PHPPdf\Document;

use PHPPdf\Util\DrawingTask;

class BasicList extends Container
{
    const TYPE_CIRCLE = '•';
    const TYPE_SQUARE = '▪';
    const TYPE_DISC = '◦';
    const TYPE_NONE = '';
    const TYPE_NUMERIC = 'numeric';
    
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
            $encoding = $glyph->getPage()->getAttribute('encoding');
            $position = $glyph->getAttribute('position');
            
            $enumerationStrategy = $glyph->getEnumerationStrategy();
            
            foreach($glyph->getChildren() as $child)
            {
                $widthOfTypeChar = $enumerationStrategy->getWidthOfCurrentEnumerationChars();
                $firstPoint = $child->getFirstPoint();
                $x = $firstPoint->getX() + ($position == BasicList::POSITION_OUTSIDE ? -$widthOfTypeChar : 0) - $child->getMarginLeft();
                $y = $firstPoint->getY() - $fontSize;

                $enumerationText = $enumerationStrategy->getCurrentEnumerationText();
                $gc->drawText($enumerationText, $x, $y, $encoding);
                $enumerationStrategy->next();
            }
        });
        
        $this->addDrawingTask($task);
    }
    
    /**
     * TODO
     * 
     * @return PHPPdf\Glyph\BasicList\EnumerationStrategy
     */
    public function getEnumerationStrategy()
    {
        if($this->getAttribute('type') === self::TYPE_NUMERIC)
        {
            $font = $this->getRecurseAttribute('font-type');
            $strategy = new OrderedEnumerationStrategy($this, $font);
        }
        else
        {
            $font = $this->getRecurseAttribute('font-type');
            $chars = (array) $this->getAttribute('type');
            
            $strategy = new UnorderedEnumerationStrategy($this, $font, $chars);            
        }

        return $strategy;
    }
    
    public function getWidthOfEnumerationChar()
    {
        $type = $this->getAttribute('type');
        $font = $this->getRecurseAttribute('font-type');
        $fontSize = $this->getRecurseAttribute('font-size');
        
        return $font->getCharsWidth(array(ord($type)), $fontSize);
    }
}