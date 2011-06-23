<?php

namespace PHPPdf\Formatter;

use PHPPdf\Glyph\Glyph,
    PHPPdf\Document;

class TableColumnFormatter extends BaseFormatter
{
    public function format(Glyph $glyph, Document $document)
    {
        $glyph->convertRelativeWidthsOfColumns();
        $glyph->reduceColumnsWidthsByMargins();
        $columnsWidths = $glyph->getWidthsOfColumns();

        $columnsMarginsLeft = $glyph->getMarginsLeftOfColumns();
        $columnsMarginsRight = $glyph->getMarginsRightOfColumns();
        
        $numberOfColumns = $glyph->getNumberOfColumns();
        $totalColumnsWidth = array_sum($columnsWidths);
        $tableWidth = $glyph->getWidth();
        $enlargeColumnWidth = $numberOfColumns ? ($tableWidth - $totalColumnsWidth)/count($columnsWidths) : 0;

        array_walk($columnsWidths, function(&$width) use($enlargeColumnWidth){
            $width += $enlargeColumnWidth;
        });

        foreach($glyph->getChildren() as $row)
        {
            foreach($row->getChildren() as /* @var $cell PHPPdf\Glyph\Table\Cell */ $cell)
            {
                $column = $cell->getNumberOfColumn();
                $colspan = $cell->getColspan();

                $newWidth = 0;

                for($i=0; $i<$colspan; $i++)
                {
                    $newWidth += $columnsWidths[$column+$i];
                }

                $cell->setWidth($newWidth);
                $cell->setMarginLeft($columnsMarginsLeft[$column]);
                $cell->setMarginRight($columnsMarginsRight[$column + $colspan - 1]);
            }
        }
    }
}