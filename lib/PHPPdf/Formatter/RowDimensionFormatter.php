<?php

namespace PHPPdf\Formatter;

use PHPPdf\Glyph\Glyph,
    PHPPdf\Document;

class RowDimensionFormatter extends BaseFormatter
{
    public function format(Glyph $glyph, Document $document)
    {
        $boundary = $glyph->getBoundary();
        $newHeight = $glyph->getMaxHeightOfCells();

        $heightDiff = $newHeight - $glyph->getHeight();

        $boundary->pointTranslate(2, 0, $heightDiff)
                 ->pointTranslate(3, 0, $heightDiff);

        $glyph->setHeight($newHeight);

        foreach((array) $glyph->getChildren() as $cell)
        {
            $heightDiff = $newHeight - $cell->getHeight();
            $cell->setHeight($newHeight);
            $cell->getBoundary()->pointTranslate(2, 0, $heightDiff)
                                ->pointTranslate(3, 0, $heightDiff);
        }
    }
}