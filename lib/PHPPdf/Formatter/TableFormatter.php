<?php

namespace PHPPdf\Formatter;

use PHPPdf\Glyph\Glyph,
    PHPPdf\Document,
    PHPPdf\Glyph\Table;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class TableFormatter extends BaseFormatter
{
    public function format(Glyph $glyph, Document $document)
    {
        $widthsOfColumns = $glyph->getWidthsOfColumns();
        $minWidthsOfColumns = $glyph->getMinWidthsOfColumns();
        $tableWidth = $glyph->getWidth();
        $totalWidth = array_sum($widthsOfColumns);

        foreach($glyph->getChildren() as $row)
        {
            $diffBetweenTableAndColumnsWidths = $tableWidth - $totalWidth;
            $translate = 0;
            foreach($row->getChildren() as /* @var $cell PHPPdf\Glyph\Table\Cell */ $cell)
            {
                $column = $cell->getNumberOfColumn();
                $newWidth = $widthsOfColumns[$column];
                $minWidth = $minWidthsOfColumns[$column];
                $widthMargin = $newWidth - $minWidth;

                if($diffBetweenTableAndColumnsWidths < 0 && -$diffBetweenTableAndColumnsWidths >= $widthMargin)
                {
                    $newWidth = $minWidth;
                    $diffBetweenTableAndColumnsWidths += $widthMargin;
                }
                elseif($diffBetweenTableAndColumnsWidths < 0)
                {
                    $newWidth += $diffBetweenTableAndColumnsWidths;
                    $diffBetweenTableAndColumnsWidths = 0;
                }

                $currentWidth = $cell->getWidth();
                $diff = $newWidth - $currentWidth;

                $minWidth = $cell->getMinWidth();

                $cell->setWidth($newWidth);
                $cell->translate($translate, 0);

                $boundary = $cell->getBoundary();

                $boundary->pointTranslate(1, $diff, 0)
                         ->pointTranslate(2, $diff, 0);

                $translate += $newWidth;
            }
        }
    }
}