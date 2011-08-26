<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Formatter;

use PHPPdf\Document,
    PHPPdf\Util\Point,
    PHPPdf\Glyph\Glyph;

class TextPositionFormatter extends BaseFormatter
{
    public function format(Glyph $glyph, Document $document)
    {
        $boundary = $glyph->getBoundary();

        list($x, $y) = $boundary->getFirstPoint()->toArray();
        list($parentX, $parentY) = $glyph->getParent()->getStartDrawingPoint();

        $lineSizes = $glyph->getLineSizes();
        $lineHeight = $glyph->getLineHeightRecursively();

        $startX = $x;

        $currentX = $x;
        $currentY = $y;
        foreach($lineSizes as $rowNumber => $width)
        {
            $newX = $x + $width;
            $newY = $currentY - $lineHeight;
            if($currentX !== $newX)
            {
                $boundary->setNext($newX, $currentY);
            }

            $boundary->setNext($newX, $newY);
            $currentX = $newX;
            $currentY = $newY;
            $x = $parentX + $glyph->getMarginLeft();
        }

        $boundary->setNext($x, $currentY);
        $currentY = $currentY + (count($lineSizes) - 1)*$lineHeight;
        $boundary->setNext($x, $currentY);
        $boundary->setNext($startX, $currentY);

        $boundary->close();
    }
}