<?php

namespace PHPPdf\Glyph\Table;

use PHPPdf\Glyph\Table\Cell;
use PHPPdf\Glyph\Container;
use PHPPdf\Glyph\Glyph;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Row extends Container
{
    private $numberOfColumns = 0;

    public function add(Glyph $glyph)
    {
        if(!$glyph instanceof Cell)
        {
            throw new \InvalidArgumentException(sprintf('Invalid child glyph type, expected PHPPdf\Glyph\Table\Cell, %s given.', get_class($glyph)));
        }

        $glyph->setNumberOfColumn($this->numberOfColumns++);

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
}