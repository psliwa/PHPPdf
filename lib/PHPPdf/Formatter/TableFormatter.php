<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Formatter;

use PHPPdf\Glyph\Glyph,
    PHPPdf\Document,
    PHPPdf\Util,
    PHPPdf\Glyph\Table;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class TableFormatter extends BaseFormatter
{
    public function format(Glyph $glyph, Document $document)
    {
        $widthsOfColumns = $glyph->getWidthsOfColumns();
        $tableWidth = $glyph->getWidth();      

        $marginsLeft = $glyph->getMarginsLeftOfColumns();
        $marginsRight = $glyph->getMarginsRightOfColumns();

        $minWidthsOfColumns = $glyph->getMinWidthsOfColumns();
        $totalWidth = array_sum($widthsOfColumns);
        $totalMargins = array_sum($marginsLeft) + array_sum($marginsRight);

        $verticalAlignFormatter = $document->getFormatter('PHPPdf\Formatter\VerticalAlignFormatter');
        
        foreach($glyph->getChildren() as $row)
        {
            $diffBetweenTableAndColumnsWidths = $tableWidth - $totalWidth - $totalMargins;
            $translate = 0;
            foreach($row->getChildren() as /* @var $cell PHPPdf\Glyph\Table\Cell */ $cell)
            {
                $column = $cell->getNumberOfColumn();
                $colspan = $cell->getColspan();
                $minWidth = $newWidth = 0;
                
                for($i=0; $i<$colspan; $i++)
                {
                    $realColumn = $column + $i;

                    $minWidth += $minWidthsOfColumns[$realColumn];
                    $newWidth += $widthsOfColumns[$realColumn];

                    if($i>0)
                    {
                        $margins = $marginsRight[$realColumn] + $marginsLeft[$realColumn];
                        $minWidth += $margins;
                        $newWidth += $margins;
                    }
                }

                $diffBetweenNewAndMinWidth = $newWidth - $minWidth;

                if($diffBetweenTableAndColumnsWidths < 0 && -$diffBetweenTableAndColumnsWidths >= $diffBetweenNewAndMinWidth)
                {
                    $newWidth = $minWidth;
                    $diffBetweenTableAndColumnsWidths += $diffBetweenNewAndMinWidth;
                }
                elseif($diffBetweenTableAndColumnsWidths < 0)
                {
                    $newWidth += $diffBetweenTableAndColumnsWidths;
                    $diffBetweenTableAndColumnsWidths = 0;
                }
                
                $cell->convertScalarAttribute('width', $tableWidth);
                $currentWidth = $cell->getWidth();
                
                $diff = $newWidth - $currentWidth;

                $minWidth = $cell->getMinWidth();

                $cell->setWidth($newWidth);
                $translate += $marginsLeft[$column];
                $cell->translate($translate, 0);
                $cell->resize($diff, 0);

                $translate += $newWidth + $marginsRight[$column];
                
                $verticalAlignFormatter->format($cell, $document);
            }
        }
    }
}