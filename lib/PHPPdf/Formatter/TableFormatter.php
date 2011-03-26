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
        $columnsWidths = $glyph->getWidthsOfColumns();

        foreach($glyph->getChildren() as $row)
        {
            $translate = 0;
            foreach($row->getChildren() as $column => /* @var $cell PHPPdf\Glyph\Table\Cell */ $cell)
            {
                $newWidth = $columnsWidths[$column];
                $currentWidth = $cell->getWidth();
                $diff = $newWidth - $currentWidth;

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