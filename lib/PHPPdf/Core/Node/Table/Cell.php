<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node\Table;

use PHPPdf\Core\Node\Container,
    PHPPdf\Core\Node\Node,
    PHPPdf\Core\Node\Listener;

/**
 * Cell of the row
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Cell extends Container
{
    private $listeners = array();
    private $numberOfColumn;

    protected static function setDefaultAttributes()
    {
        parent::setDefaultAttributes();
        
        static::addAttribute('colspan', 1);
    }
    
    protected static function initializeType()
    {
        parent::initializeType();
        static::setAttributeGetters(array('colspan' => 'getColspan'));
        static::setAttributeSetters(array('colspan' => 'setColspan'));
    }
    
    public function getColspan()
    {
        return $this->getAttributeDirectly('colspan');
    }
    
    public function setColspan($colspan)
    {
        $this->setAttributeDirectly('colspan', $colspan);
    }

    public function getFloat()
    {
        return self::FLOAT_LEFT;
    }

    public function getWidth()
    {
        $width = parent::getWidth();

        if($width === null)
        {
            $width = 0;
        }

        return $width;
    }

    /**
     * @return PHPPdf\Core\Node\Table
     */
    public function getTable()
    {
        return $this->getAncestorByType('PHPPdf\Core\Node\Table');
    }

    public function addListener(Listener $listener)
    {
        $this->listeners[] = $listener;
    }

    public function setParent(Container $node)
    {
        parent::setParent($node);

        foreach($this->listeners as $listener)
        {
            $listener->parentBind($this);
        }
    }

    protected function setAttributeDirectly($name, $value)
    {
        $oldValue = $this->getAttributeDirectly($name);

        parent::setAttributeDirectly($name, $value);
        
        foreach($this->listeners as $listener)
        {
            $listener->attributeChanged($this, $name, $oldValue);
        }
    }

    public function setNumberOfColumn($column)
    {
        $this->numberOfColumn = (int) $column;
    }

    public function getNumberOfColumn()
    {
        return $this->numberOfColumn;
    }
}