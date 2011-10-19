<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Formatter;

use PHPPdf\Core\Document,
    PHPPdf\Core\Point,
    PHPPdf\Core\Node\Node;

class TextPositionFormatter extends BaseFormatter
{
    public function format(Node $node, Document $document)
    {
        $boundary = $node->getBoundary();

        list($x, $y) = $boundary->getFirstPoint()->toArray();
        list($parentX, $parentY) = $node->getParent()->getStartDrawingPoint();

        $lineSizes = $node->getLineSizes();
        $lineHeight = $node->getLineHeightRecursively();

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
            $x = $parentX + $node->getMarginLeft();
        }

        $boundary->setNext($x, $currentY);
        $currentY = $currentY + (count($lineSizes) - 1)*$lineHeight;
        $boundary->setNext($x, $currentY);
        $boundary->setNext($startX, $currentY);

        $boundary->close();
    }
}