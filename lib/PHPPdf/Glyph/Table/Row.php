<?php

namespace PHPPdf\Glyph\Table;

use PHPPdf\Glyph\Table\Cell,
    PHPPdf\Glyph\Container,
    PHPPdf\Glyph\AttributeListener,
    PHPPdf\Glyph\Glyph;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Row extends Container implements AttributeListener
{
    private $numberOfColumns = 0;
    private $maxHeightOfCells = 0;

    public function add(Glyph $glyph)
    {
        if(!$glyph instanceof Cell)
        {
            throw new \InvalidArgumentException(sprintf('Invalid child glyph type, expected PHPPdf\Glyph\Table\Cell, %s given.', get_class($glyph)));
        }

        $glyph->setNumberOfColumn($this->numberOfColumns++);
        $parent = $this->getParent();

        if($parent)
        {
            $glyph->addAttributeListener($parent);
        }

        $glyph->addAttributeListener($this);

        return parent::add($glyph);
    }

    public function getHeight()
    {
        $height = parent::getHeight();

        if($height === null)
        {
            $height = $this->getAncestorByType('PHPPdf\Glyph\Table')->getRowHeight();
        }

        return $height;
    }

    public function getWidth()
    {
        return $this->getParent()->getWidth();
    }

    /**
     * Row can not be splitted
     */
    public function split($height)
    {
        return null;
    }

    public function reset()
    {
        parent::reset();
        $this->numberOfColumns = 0;
    }

    public function attributeChanged(Glyph $glyph, $attributeName, $oldValue)
    {
        if($attributeName === 'height')
        {
            $height = $glyph->getHeight();

            if($height > $this->maxHeightOfCells)
            {
                $this->maxHeightOfCells = $height;
            }
        }
    }

    public function getMaxHeightOfCells()
    {
        return $this->maxHeightOfCells;
    }
}