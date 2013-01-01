<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node;

use PHPPdf\Core\DrawingTaskHeap;
use PHPPdf\Core\Node\BasicList\EnumerationStrategyFactory;
use PHPPdf\Core\Node\BasicList\ImageEnumerationStrategy,
    PHPPdf\Core\Node\BasicList\EnumerationStrategy,
    PHPPdf\Core\Node\BasicList\OrderedEnumerationStrategy,
    PHPPdf\Core\Node\BasicList\UnorderedEnumerationStrategy,
    PHPPdf\Core\Document,
    PHPPdf\Core\DrawingTask;

/**
 * Class of the list element
 * 
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
    
    const LIST_POSITION_INSIDE = 'inside';
    const LIST_POSITION_OUTSIDE = 'outside';
    
    private $enumerationStrategy;
    private $omitEnumerationOfFirstElement = false;

    protected static function setDefaultAttributes()
    {
        parent::setDefaultAttributes();
        
        static::addAttribute('type', self::TYPE_CIRCLE);
        static::addAttribute('image');
        static::addAttribute('list-position', self::LIST_POSITION_INSIDE);
    }
    
    protected static function initializeType()
    {
        parent::initializeType();
        
        static::setAttributeSetters(array('type' => 'setType', 'image' => 'setImage'));
        static::setAttributeGetters(array('type' => 'getType', 'image' => 'getImage'));
    }
    
    /**
     * @internal
     */
    public function isOmitEnumerationOfFirstElement()
    {
        return $this->omitEnumerationOfFirstElement;
    }
    
    /**
     * @internal
     */
    public function setOmitEnumerationOfFirstElement($flag)
    {
        $this->omitEnumerationOfFirstElement = (boolean) $flag;
    }
    
    /**
     * Sets list type
     * 
     * Implementation of this method also clears enumeration strategy property
     * 
     * @param string List type
     */
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
    
    protected function beforeFormat(Document $document)
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
    
    protected function doDraw(Document $document, DrawingTaskHeap $tasks)
    {
        parent::doDraw($document, $tasks);
        
        $tasks->insert(new DrawingTask(function(Node $node, Document $document) {
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
        }, array($this, $document)));
    }
    
    /**
     * @return EnumerationStrategy
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
        
        return $font->getWidthOfText($type, $fontSize);
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