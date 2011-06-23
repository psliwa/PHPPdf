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
    private $marginsOfColumns = array(
        'margin-left' => array(),
        'margin-right' => array(),
    );

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
            $this->setColumnMarginIfNecessary($cell, 'margin-left');
            $this->setColumnMarginIfNecessary($cell, 'margin-right');
        }

        return parent::add($glyph);
    }

    private function setColumnWidthIfNecessary(Glyph $glyph)
    {
        $width = $glyph->getWidth();
        $columnNumber = $glyph->getNumberOfColumn();
        $colspan = $glyph->getAttribute('colspan');

        $isWidthRelative = strpos($width, '%') !== false;
        
        $widthPerColumn = $width / $colspan;
        
        if($isWidthRelative)
        {
            $widthPerColumn .= '%';
        }

        for($i=0; $i<$colspan; $i++)
        {
            $realColumnNumber = $columnNumber + $i;
            if(!isset($this->widthsOfColumns[$realColumnNumber]) || $widthPerColumn > $this->widthsOfColumns[$realColumnNumber])
            {
                $this->widthsOfColumns[$realColumnNumber] = $widthPerColumn;
            }
        }
    }

    private function setColumnMarginIfNecessary(Glyph $glyph, $marginType)
    {
        $margin = $glyph->getAttribute($marginType);
        $columnNumber = $glyph->getNumberOfColumn();

        if(!isset($this->marginsOfColumns[$marginType][$columnNumber]) || $margin > $this->marginsOfColumns[$marginType][$columnNumber])
        {
            $this->marginsOfColumns[$marginType][$columnNumber] = $margin;
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
        elseif(in_array($attributeName, array('margin-left', 'margin-right')))
        {
            $this->setColumnMarginIfNecessary($glyph, $attributeName);
        }
    }

    public function parentBind(Glyph $glyph)
    {
        $this->setColumnWidthIfNecessary($glyph);
        $this->setColumnMarginIfNecessary($glyph, 'margin-left');
        $this->setColumnMarginIfNecessary($glyph, 'margin-right');
    }

    public function getWidthsOfColumns()
    {
        if(!$this->widthsOfColumns)
        {
            $numberOfColumns = $this->getNumberOfColumns();
            $this->widthsOfColumns = $numberOfColumns > 0 ? array_fill(0, $this->getNumberOfColumns(), 0) : array();
        }

        return $this->widthsOfColumns;
    }

    private function setWidthsOfColumns($widthsOfColumns)
    {
        $this->widthsOfColumns = $widthsOfColumns;
    }
    
    public function convertRelativeWidthsOfColumns()
    {
        $tableWidth = $this->getWidth();
        array_walk($this->widthsOfColumns, function(&$width, $key) use($tableWidth){
            $width = \PHPPdf\Util::convertFromPercentageValue($width, $tableWidth);
        });
    }

    public function getNumberOfColumns()
    {
        return count($this->widthsOfColumns);
    }

    public function getMinWidthsOfColumns()
    {
        $minWidthsOfColumns = array();
        foreach($this->getChildren() as $row)
        {
            foreach($row->getChildren() as $cell)
            {
                $column = $cell->getNumberOfColumn();
                $colspan = $cell->getColspan();
                $minWidthPerColumn = $cell->getMinWidth() / $colspan;

                for($i=0; $i<$colspan; $i++)
                {
                    $realColumn = $column + $i;
                    $minWidthsOfColumns[$realColumn] = isset($minWidthsOfColumns[$realColumn]) ? max($minWidthsOfColumns[$realColumn], $minWidthPerColumn) : $minWidthPerColumn;
                }
            }
        }

        return $minWidthsOfColumns;
    }

    public function getMarginsLeftOfColumns()
    {
        return $this->marginsOfColumns['margin-left'];
    }

    public function getMarginsRightOfColumns()
    {
        return $this->marginsOfColumns['margin-right'];
    }

    private function setMarginsLeftOfColumns(array $margins)
    {
        $this->marginsOfColumns['margin-left'] = $margins;
    }
    
    private function setMarginsRightOfColumns(array $margins)
    {
        $this->marginsOfColumns['margin-right'] = $margins;
    }

    public function reduceColumnsWidthsByMargins()
    {
        $marginsLeft = $this->getMarginsLeftOfColumns();
        $marginsRight = $this->getMarginsRightOfColumns();

        array_walk($this->widthsOfColumns, function(&$widthOfColumn, $columnNumber) use($marginsLeft, $marginsRight)
        {
            $widthOfColumn -= $marginsLeft[$columnNumber] + $marginsRight[$columnNumber];
        });
    }
}