<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Formatter;

use PHPPdf\Node\Node,
    PHPPdf\Node as Nodes,
    PHPPdf\Document;

class FirstPointPositionFormatter extends BaseFormatter
{
    public function format(Node $node, Document $document)
    {
        $node->makeAttributesSnapshot(array('height', 'width'));
        $boundary = $node->getBoundary();
        if($boundary->isClosed())
        {
            return;
        }

        $parent = $node->getParent();

        list($parentX, $parentY) = $parent->getStartDrawingPoint();

        $startX = $node->getMarginLeft() + $parentX;
        $startY = $parentY - $node->getMarginTop();

        $this->setNodesPosition($node, $startX, $startY);

        $boundary->setNext($startX, $startY);
    }

    private function setNodesPosition(Node $node, &$preferredXCoord, &$preferredYCoord)
    {
        $parent = $node->getParent();
        list($parentX, $parentY) = $parent->getStartDrawingPoint();

        $previousSibling = $node->getPreviousSibling();

        if($previousSibling)
        {
            list($siblingEndX, $siblingEndY) = $previousSibling->getDiagonalPoint()->toArray();
            list($siblingStartX, $siblingStartY) = $previousSibling->getFirstPoint()->toArray();

            if($this->isNodeInSameRowAsPreviousSibling($node, $previousSibling))
            {
                $preferredXCoord += $previousSibling->getMarginRight() + $siblingEndX - $parentX;
                $preferredYCoord = $siblingStartY + $previousSibling->getMarginTop() - $node->getMarginTop();
                if($previousSibling instanceof Nodes\Text)
                {
                    $preferredYCoord -= $previousSibling->getLineHeightRecursively() * (count($previousSibling->getLineSizes()) - 1);
                }
            }
            else
            {
                $preferredYCoord = $siblingEndY - ($previousSibling->getMarginBottom() + $node->getMarginTop());
            }
        }
    }

    private function isNodeInSameRowAsPreviousSibling(Node $node, Node $previousSibling)
    {
        $oneOfNodesIsInline = $previousSibling->getAttribute('display') === Nodes\Node::DISPLAY_INLINE && $node->getDisplay() === Nodes\Node::DISPLAY_INLINE;

        $parent = $node->getParent();
        $parentBoundary = $parent->getBoundary();

        list($prevX) = $previousSibling->getEndDrawingPoint();
        $endX = $prevX + $previousSibling->getMarginRight() + $node->getMarginLeft() + $node->getWidth();
        $parentEndX = $parentBoundary->getFirstPoint()->getX() + $parent->getWidth();

        $rowIsOverflowed = !$node instanceof Nodes\Text && $parentEndX < $endX && $previousSibling->getFloat() !== Nodes\Node::FLOAT_RIGHT;

        return !$rowIsOverflowed && $oneOfNodesIsInline;
    }
}