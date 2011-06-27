<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Formatter;

use PHPPdf\Glyph\Glyph,
    PHPPdf\Document;

class RowDimensionFormatter extends BaseFormatter
{
    public function format(Glyph $glyph, Document $document)
    {
        $boundary = $glyph->getBoundary();
        $verticalMargins = $glyph->getMarginsBottomOfCells() + $glyph->getMarginsTopOfCells();
        $newHeight = $glyph->getMaxHeightOfCells() + $verticalMargins;

        $heightDiff = $newHeight - $glyph->getHeight();

        $boundary->pointTranslate(2, 0, $heightDiff)
                 ->pointTranslate(3, 0, $heightDiff);

        $glyph->setHeight($newHeight);

        foreach((array) $glyph->getChildren() as $cell)
        {
            $heightDiff = $glyph->getMaxHeightOfCells() - $cell->getHeight();
            $cell->setHeight($glyph->getMaxHeightOfCells());
            $cell->getBoundary()->pointTranslate(2, 0, $heightDiff)
                                ->pointTranslate(3, 0, $heightDiff);
            $cell->translate(0, $glyph->getMarginsTopOfCells());
        }
    }
}