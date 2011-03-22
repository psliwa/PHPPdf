<?php

namespace PHPPdf\Glyph\Table;

use PHPPdf\Glyph\Container,
    PHPPdf\Glyph\Glyph,
    PHPPdf\Glyph\AttributeListener;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Cell extends Container
{
    private $attributeListeners = array();
    private $numberOfColumn;

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

    public function addAttributeListener(AttributeListener $listener)
    {
        $this->attributeListeners[] = $listener;
    }

    public function setAttribute($name, $value)
    {
        $oldValue = $this->getAttribute($name);

        parent::setAttribute($name, $value);
        
        foreach($this->attributeListeners as $listener)
        {
            $listener->attributeChanged($this, $name, $oldValue);
        }

        return $this;
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