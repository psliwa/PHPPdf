<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph\Table;

use PHPPdf\Glyph\Table\Cell,
    PHPPdf\Glyph\Container,
    PHPPdf\Glyph\Listener,
    PHPPdf\Glyph\Glyph;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Row extends Container implements Listener
{
    private $numberOfColumns = 0;
    private $maxHeightOfCells = 0;
    private $marginsOfCells = array(
        'margin-top' => 0,
        'margin-bottom' => 0,
    );

    public function add(Glyph $glyph)
    {
        if(!$glyph instanceof Cell)
        {
            throw new \InvalidArgumentException(sprintf('Invalid child glyph type, expected PHPPdf\Glyph\Table\Cell, %s given.', get_class($glyph)));
        }

        $glyph->setNumberOfColumn($this->numberOfColumns);
        $this->numberOfColumns += $glyph->getColspan();
        $parent = $this->getParent();

        if($parent)
        {
            $glyph->addListener($parent);
        }

        $glyph->addListener($this);

        $this->setMaxHeightOfCellsIfHeightOfPassedCellIsGreater($glyph);
        $this->setCellMarginIfNecessary($glyph, 'margin-top');
        $this->setCellMarginIfNecessary($glyph, 'margin-bottom');

        return parent::add($glyph);
    }

    private function setMaxHeightOfCellsIfHeightOfPassedCellIsGreater(Cell $glyph)
    {
        $height = $glyph->getHeight();

        if($height > $this->maxHeightOfCells)
        {
            $this->maxHeightOfCells = $height;
        }
    }

    private function setCellMarginIfNecessary(Cell $cell, $marginType)
    {
        $margin = $cell->getAttribute($marginType);

        if($margin > $this->marginsOfCells[$marginType])
        {
            $this->marginsOfCells[$marginType] = $margin;
        }
    }

    public function getMarginsTopOfCells()
    {
        return $this->marginsOfCells['margin-top'];
    }

    public function getMarginsBottomOfCells()
    {
        return $this->marginsOfCells['margin-bottom'];
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
            $this->setMaxHeightOfCellsIfHeightOfPassedCellIsGreater($glyph);
        }
    }

    public function parentBind(Glyph $glyph)
    {
        $this->setMaxHeightOfCellsIfHeightOfPassedCellIsGreater($glyph);
    }

    public function getMaxHeightOfCells()
    {
        return $this->maxHeightOfCells;
    }
}