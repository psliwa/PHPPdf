<?php

namespace PHPPdf\Glyph\Table;

use PHPPdf\Glyph\Container,
    PHPPdf\Glyph\Glyph,
    PHPPdf\Glyph\Listener;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Cell extends Container
{
    private $listeners = array();
    private $numberOfColumn;

    public function initialize()
    {
        parent::initialize();

        $this->addAttribute('colspan', 1, 'getColspan', 'setColspan');
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
     * @return PHPPdf\Glyph\Table
     */
    public function getTable()
    {
        return $this->getAncestorByType('PHPPdf\Glyph\Table');
    }

    public function addListener(Listener $listener)
    {
        $this->listeners[] = $listener;
    }

    public function setParent(Container $glyph)
    {
        parent::setParent($glyph);

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