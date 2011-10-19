<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Formatter;

use PHPPdf\Core\Node\Node,
    PHPPdf\Core\Document;

class RowDimensionFormatter extends BaseFormatter
{
    public function format(Node $node, Document $document)
    {
        $boundary = $node->getBoundary();
        $verticalMargins = $node->getMarginsBottomOfCells() + $node->getMarginsTopOfCells();
        $newHeight = $node->getMaxHeightOfCells() + $verticalMargins;

        $heightDiff = $newHeight - $node->getHeight();

        $boundary->pointTranslate(2, 0, $heightDiff)
                 ->pointTranslate(3, 0, $heightDiff);

        $node->setHeight($newHeight);

        foreach((array) $node->getChildren() as $cell)
        {
            $heightDiff = $node->getMaxHeightOfCells() - $cell->getHeight();
            $cell->setHeight($node->getMaxHeightOfCells());
            $cell->getBoundary()->pointTranslate(2, 0, $heightDiff)
                                ->pointTranslate(3, 0, $heightDiff);
            $cell->translate(0, $node->getMarginsTopOfCells());
        }
    }
}