<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Node;

use PHPPdf\Node\BasicList\EnumerationStrategyFactory;

use PHPPdf\Node\BasicList\ImageEnumerationStrategy,
    PHPPdf\Node\BasicList\EnumerationStrategy,
    PHPPdf\Node\BasicList\OrderedEnumerationStrategy,
    PHPPdf\Node\BasicList\UnorderedEnumerationStrategy,
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
    const TYPE_DECIMAL = 'decimal';
    const TYPE_DECIMAL_LEADING_ZERO = 'decimal-leading-zero';
    const TYPE_LOWER_ALPHA = 'lower-alpha';
    const TYPE_UPPER_ALPHA = 'upper-alpha';
    const TYPE_IMAGE = 'image';
    
    const POSITION_INSIDE = 'inside';
    const POSITION_OUTSIDE = 'outside';
    
    private $enumerationStrategy;
    private $omitEnumerationOfFirstElement = false;

    public function initialize()
    {
        parent::initialize();
        
        $this->addAttribute('type', self::TYPE_CIRCLE);
        $this->addAttribute('image');
        $this->addAttribute('position', self::POSITION_INSIDE);
    }
    
    protected static function initializeType()
    {
        parent::initializeType();
        
        static::setAttributeSetters(array('type' => 'setType', 'image' => 'setImage'));
        static::setAttributeGetters(array('type' => 'getType', 'image' => 'getImage'));
    }
    
    public function isOmitEnumerationOfFirstElement()
    {
        return $this->omitEnumerationOfFirstElement;
    }
    
    public function setOmitEnumerationOfFirstElement($flag)
    {
        $this->omitEnumerationOfFirstElement = (boolean) $flag;
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
        $this->setAttributeDirectly('image', $image);
    }
    
    public function preFormat(Document $document)
    {
        $image = $this->getAttribute('image');

        if(is_string($image))
        {
            $image = $document->createImage($image);
            $this->setAttribute('image', $image);
        }
        
    }
    
    public function getImage()
    {
        return $this->getAttributeDirectly('image');
    }
    
    protected function doDraw(Document $document)
    {
        parent::doDraw($document);
        
        $task = new DrawingTask(function(Node $node, Document $document) {
            $gc = $node->getGraphicsContext();

            $enumerationStrategy = $node->getEnumerationStrategy();
            $enumerationStrategy->setIndex(0);
            
            foreach($node->getChildren() as $i => $child)
            {
                if($node->isOmitEnumerationOfFirstElement())
                {
                    $node->setOmitEnumerationOfFirstElement(false);
                    $enumerationStrategy->incrementIndex();
                }
                else
                {
                    $enumerationStrategy->drawEnumeration($document, $node, $gc, $i);
                }
            }

            $enumerationStrategy->reset();
        }, array($this, $document));
        
        $this->addDrawingTask($task);
    }
    
    /**
     * TODO: use factory
     * 
     * @return PHPPdf\Node\BasicList\EnumerationStrategy
     */
    public function getEnumerationStrategy()
    {
        if($this->enumerationStrategy === null)
        {
            $this->assignEnumerationStrategyFromFactory();
        }
        
        return $this->enumerationStrategy;
    }
    
    public function assignEnumerationStrategyFromFactory()
    {
        $this->enumerationStrategy = $this->enumerationStrategyFactory->create($this->getAttribute('type'));
    }
    
    public function setEnumerationStrategyFactory(EnumerationStrategyFactory $factory)
    {
        $this->enumerationStrategyFactory = $factory;
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
    
    protected function doBreakAt($height)
    {
        $numberOfChildren = $this->getNumberOfChildren();
        
        $node = parent::doBreakAt($height);
        
        $currentNumberOfChildren = $this->getNumberOfChildren() + ($node ? $node->getNumberOfChildren() : 0);
        
        if($node && $currentNumberOfChildren > $numberOfChildren)
        {
            $node->setOmitEnumerationOfFirstElement(true);
        }
        
        return $node;
    }
    
    public function copy()
    {
        $node = parent::copy();
        $node->setOmitEnumerationOfFirstElement(false);
        
        return $node;
    }
}