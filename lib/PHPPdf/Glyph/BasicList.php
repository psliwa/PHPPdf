<?php

namespace PHPPdf\Glyph;

use PHPPdf\Glyph\BasicList\ImageEnumerationStrategy,
    PHPPdf\Glyph\BasicList\EnumerationStrategy,
    PHPPdf\Glyph\BasicList\OrderedEnumerationStrategy,
    PHPPdf\Glyph\BasicList\UnorderedEnumerationStrategy,
    PHPPdf\Document,
    PHPPdf\Util\DrawingTask;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class BasicList extends Container
{
    const TYPE_CIRCLE = '•';
    const TYPE_SQUARE = '▪';
    const TYPE_DISC = '◦';
    const TYPE_NONE = '';
    const TYPE_NUMERIC = 'numeric';
    const TYPE_IMAGE = 'image';
    
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
        
        static::setAttributeSetters(array('type' => 'setType', 'image' => 'setImage'));
        static::setAttributeGetters(array('type' => 'getType', 'image' => 'getImage'));
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
    
    public function setImage($image)
    {
        if(!$image instanceof \Zend_Pdf_Resource_Image)
        {
            $image = \Zend_Pdf_Image::imageWithPath($image);
        }
        
        $this->setAttributeDirectly('image', $image);
    }
    
    public function getImage()
    {
        return $this->getAttributeDirectly('image');
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
     * TODO: use factory
     * 
     * @return PHPPdf\Glyph\BasicList\EnumerationStrategy
     */
    public function getEnumerationStrategy()
    {
        if($this->enumerationStrategy === null)
        {
            if($this->getAttribute('type') === self::TYPE_NUMERIC)
            {
                $strategy = new OrderedEnumerationStrategy();
            }
            elseif($this->getAttribute('type') === self::TYPE_IMAGE)
            {
                $strategy = new ImageEnumerationStrategy();
            }
            else
            {                
                $strategy = new UnorderedEnumerationStrategy();            
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
    
    protected function doSplit($height)
    {
        $product = parent::doSplit($height);
        
        if($product !== null)
        {
            $initialIndex = count($this->getChildren()) + $this->getEnumerationStrategy()->getInitialIndex();
            $product->getEnumerationStrategy()->setInitialIndex($initialIndex);
        }
        
        return $product;
    }
    
    public function copy()
    {
        $copy = parent::copy();
        
        if($copy->enumerationStrategy !== null)
        {
            $copy->enumerationStrategy = clone $this->enumerationStrategy;
        }
        
        return $copy;
    }
}