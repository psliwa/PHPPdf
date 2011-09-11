<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Node;

use PHPPdf\Node\Table\Cell,
    PHPPdf\Node\Node,
    PHPPdf\Node\Table\Row;

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

    protected static function setDefaultAttributes()
    {
        parent::setDefaultAttributes();
        
        static::addAttribute('row-height');
    }

    public function add(Node $node)
    {
        if(!$node instanceof Row)
        {
            throw new \InvalidArgumentException(sprintf('Invalid child node type, expected PHPPdf\Node\Table\Row, %s given.', get_class($node)));
        }

        foreach($node->getChildren() as $cell)
        {
            $this->updateColumnDataIfNecessary($cell);
        }

        return parent::add($node);
    }
    
    private function updateColumnDataIfNecessary(Cell $cell)
    {
        $this->setColumnWidthIfNecessary($cell);
        foreach(array('margin-left', 'margin-right') as $attribute)
        {
            $this->setColumnMarginIfNecessary($cell, $attribute);
        }
    }

    private function setColumnWidthIfNecessary(Node $node)
    {
        $width = $node->getWidth();
        $columnNumber = $node->getNumberOfColumn();
        $colspan = $node->getAttribute('colspan');

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

    private function setColumnMarginIfNecessary(Node $node, $marginType)
    {
        $margin = $node->getAttribute($marginType);
        $columnNumber = $node->getNumberOfColumn();

        if(!isset($this->marginsOfColumns[$marginType][$columnNumber]) || $margin > $this->marginsOfColumns[$marginType][$columnNumber])
        {
            $this->marginsOfColumns[$marginType][$columnNumber] = $margin;
        }
    }

    protected function doBreakAt($height)
    {
        $broken = parent::doBreakAt($height);

        if($broken)
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

        return $broken;
    }

    public function attributeChanged(Node $node, $attributeName, $oldValue)
    {
        if($attributeName == 'width')
        {
            $this->setColumnWidthIfNecessary($node);
        }
        elseif(in_array($attributeName, array('margin-left', 'margin-right')))
        {
            $this->setColumnMarginIfNecessary($node, $attributeName);
        }
    }

    public function parentBind(Node $node)
    {
        $this->updateColumnDataIfNecessary($node);
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
        array_walk($this->widthsOfColumns, function(&$width, $key, $tableWidth){
            $width = \PHPPdf\Util::convertFromPercentageValue($width, $tableWidth);
        }, $tableWidth);
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