<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Formatter;

use PHPPdf\Formatter\BaseFormatter,
    PHPPdf\Node as Nodes,
    PHPPdf\Document;

/**
 * Calculates real dimension of compose node
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ContainerDimensionFormatter extends BaseFormatter
{
    public function format(Nodes\Node $node, Document $document)
    {
        $minX = $maxX = $minY = $maxY = null;
        foreach($node->getChildren() as $child)
        {
            $boundary = $child->getBoundary();
            $firstPoint = $boundary->getFirstPoint();
            $diagonalPoint = $boundary->getDiagonalPoint();

            $childMinX = $firstPoint->getX() - $child->getMarginLeft();
            $childMaxX = $diagonalPoint->getX() + $child->getMarginRight();
            $childMinY = $diagonalPoint->getY() - $child->getMarginBottom();
            $childMaxY = $firstPoint->getY() + $child->getMarginTop();

            $this->changeValueIfIsLess($maxX, $childMaxX);
            $this->changeValueIfIsLess($maxY, $childMaxY);

            $this->changeValueIfIsGreater($minX, $childMinX);
            $this->changeValueIfIsGreater($minY, $childMinY);
        }

        $paddingVertical = $node->getPaddingTop() + $node->getPaddingBottom();
        $paddingHorizontal = $node->getPaddingLeft() + $node->getPaddingRight();

        $realHeight = $paddingVertical + ($maxY - $minY);
        $realWidth = $paddingHorizontal + ($maxX - $minX);

        $display = $node->getAttribute('display');

        if($realHeight > $node->getHeight())
        {
            $node->setHeight($realHeight);
        }

        if($display === Nodes\Node::DISPLAY_INLINE || $realWidth > $node->getWidth())
        {
            $node->setWidth($realWidth);
        }
    }

    private function changeValueIfIsLess(&$value, $valueToSet)
    {
        if($value === null || $value < $valueToSet)
        {
            $value = $valueToSet;
        }
    }

    private function changeValueIfIsGreater(&$value, $valueToSet)
    {
        if($value === null || $value > $valueToSet)
        {
            $value = $valueToSet;
        }
    }
}