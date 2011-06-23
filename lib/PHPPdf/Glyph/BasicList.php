<?php

namespace PHPPdf\Glyph;

use PHPPdf\Glyph\BasicList\EnumerationStrategy;

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
    
    private $enumerationStrategy;
    
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
        static::setAttributeGetters(array('type' => 'getType'));
    }
    
    public function setType($type)
    {
        $const = sprintf('%s::TYPE_%s', __CLASS__, strtoupper($type));
        
        if(defined($const))
        {
            $type = constant($const);
        }
        
        $this->setAttributeDirectly('type', $type);
        
        $this->enumerationStrategy = null;
    }
    
    public function getType()
    {
        return $this->getAttributeDirectly('type');
    }
    
    protected function doDraw(Document $document)
    {
        parent::doDraw($document);
        
        $glyph = $this;
        $task = new DrawingTask(function() use($glyph){
            $gc = $glyph->getGraphicsContext();

            $enumerationStrategy = $glyph->getEnumerationStrategy();
            
            foreach($glyph->getChildren() as $i => $child)
            {
                $enumerationStrategy->drawEnumeration($glyph, $gc, $i);
            }

            $enumerationStrategy->reset();
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
        if($this->enumerationStrategy === null)
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
            
            $this->enumerationStrategy = $strategy;
        }

        return $this->enumerationStrategy;
    }
    
    public function setEnumerationStrategy(EnumerationStrategy $enumerationStrategy)
    {
        $this->enumerationStrategy = $enumerationStrategy;
    }
    
    public function getWidthOfEnumerationChar()
    {
        $type = $this->getAttribute('type');
        $font = $this->getRecurseAttribute('font-type');
        $fontSize = $this->getRecurseAttribute('font-size');
        
        return $font->getCharsWidth(array(ord($type)), $fontSize);
    }
}