<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Formatter;

use PHPPdf\Node\Node,
    PHPPdf\Document;

class TableColumnFormatter extends BaseFormatter
{
    public function format(Node $node, Document $document)
    {
        $node->convertRelativeWidthsOfColumns();
        $node->reduceColumnsWidthsByMargins();
        $columnsWidths = $node->getWidthsOfColumns();

        $columnsMarginsLeft = $node->getMarginsLeftOfColumns();
        $columnsMarginsRight = $node->getMarginsRightOfColumns();
        
        $numberOfColumns = $node->getNumberOfColumns();
        $totalColumnsWidth = array_sum($columnsWidths);
        $tableWidth = $node->getWidth();
        $enlargeColumnWidth = $numberOfColumns ? ($tableWidth - $totalColumnsWidth)/count($columnsWidths) : 0;

        array_walk($columnsWidths, function(&$width) use($enlargeColumnWidth){
            $width += $enlargeColumnWidth;
        });

        foreach($node->getChildren() as $row)
        {
            foreach($row->getChildren() as /* @var $cell PHPPdf\Node\Table\Cell */ $cell)
            {
                $column = $cell->getNumberOfColumn();
                $colspan = $cell->getColspan();

                $newWidth = 0;

                for($i=0; $i<$colspan; $i++)
                {
                    $newWidth += $columnsWidths[$column+$i];
                }

                $horizontalPaddings = $cell->getPaddingLeft() + $cell->getPaddingRight();
                $cell->setWidth($newWidth - $horizontalPaddings);
                $cell->setMarginLeft($columnsMarginsLeft[$column]);
                $cell->setMarginRight($columnsMarginsRight[$column + $colspan - 1]);
            }
        }
    }
}