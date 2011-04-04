<?php

namespace PHPPdf\Formatter;

use PHPPdf\Glyph\Glyph,
    PHPPdf\Document;

class TableColumnWidthFormatter extends BaseFormatter
{
    public function format(Glyph $glyph, Document $document)
    {
        $columnsWidths = $glyph->getWidthsOfColumns();
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
                $newWidth = $columnsWidths[$column];
                $cell->setWidth($newWidth);
            }
        }
    }
}