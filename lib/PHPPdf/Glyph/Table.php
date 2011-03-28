<?php

namespace PHPPdf\Glyph;

use PHPPdf\Glyph\Glyph,
    PHPPdf\Glyph\Table\Row;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Table extends Container implements Listener
{
    private $widthsOfColumns = array();

    public function initialize()
    {
        parent::initialize();

        $this->addAttribute('row-height');
    }

    public function add(Glyph $glyph)
    {
        if(!$glyph instanceof Row)
        {
            throw new \InvalidArgumentException(sprintf('Invalid child glyph type, expected PHPPdf\Glyph\Table\Row, %s given.', get_class($glyph)));
        }

        foreach($glyph->getChildren() as $cell)
        {
            $this->setColumnWidthIfNecessary($cell);
        }

        return parent::add($glyph);
    }

    private function setColumnWidthIfNecessary(Glyph $glyph)
    {
        $width = $glyph->getWidth();
        $columnNumber = $glyph->getNumberOfColumn();

        if(!isset($this->widthsOfColumns[$columnNumber]) || $width > $this->widthsOfColumns[$columnNumber])
        {
            $this->widthsOfColumns[$columnNumber] = $width;
        }
    }

    protected function doSplit($height)
    {
        $splited = parent::doSplit($height);

        if($splited)
        {
            $height = 0;
            foreach($this->getChildren() as $row)
            {
                $height += $row->getHeight() + $row->getMarginTop() + $row->getMarginBottom();
            }

            $oldHeight = $this->getHeight();
            $this->setHeight($height);
            $diff = $oldHeight - $height;

            $boundary = $this->getBoundary();
            $boundary->pointTranslate(2, 0, -$diff);
            $boundary->pointTranslate(3, 0, -$diff);
        }

        return $splited;
    }

    public function attributeChanged(Glyph $glyph, $attributeName, $oldValue)
    {
        if($attributeName == 'width')
        {
            $this->setColumnWidthIfNecessary($glyph);
        }
    }

    public function parentBind(Glyph $glyph)
    {
        $this->setColumnWidthIfNecessary($glyph);
    }

    public function getWidthsOfColumns()
    {
        if(!$this->widthsOfColumns)
        {
            $this->widthsOfColumns = array_fill(0, $this->getNumberOfColumns(), 0);
        }

        return $this->widthsOfColumns;
    }

    private function setWidthsOfColumns($widthsOfColumns)
    {
        $this->widthsOfColumns = $widthsOfColumns;
    }

    public function getNumberOfColumns()
    {
        return count($this->widthsOfColumns);
    }
}