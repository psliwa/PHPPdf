<?php

namespace PHPPdf\Formatter;

use PHPPdf\Glyph\Glyph,
    PHPPdf\Glyph as Glyphs,
    PHPPdf\Document;

class FirstPointPositionFormatter extends BaseFormatter
{
    public function format(Glyph $glyph, Document $document)
    {
        $glyph->makeAttributesSnapshot();
        $boundary = $glyph->getBoundary();
        if($boundary->isClosed())
        {
            return;
        }

        $parent = $glyph->getParent();

        list($parentX, $parentY) = $parent->getStartDrawingPoint();

        $startX = $glyph->getMarginLeft() + $parentX;
        $startY = $parentY - $glyph->getMarginTop();

        $this->setGlyphsPosition($glyph, $startX, $startY);

        $boundary->setNext($startX, $startY);
    }

    private function setGlyphsPosition(Glyph $glyph, &$preferredXCoord, &$preferredYCoord)
    {
        $parent = $glyph->getParent();
        list($parentX, $parentY) = $parent->getStartDrawingPoint();

        $previousSibling = $glyph->getPreviousSibling();

        if($previousSibling)
        {
            list($siblingEndX, $siblingEndY) = $previousSibling->getDiagonalPoint()->toArray();
            list($siblingStartX, $siblingStartY) = $previousSibling->getFirstPoint()->toArray();

            if($this->isGlyphInSameRowAsPreviousSibling($glyph, $previousSibling))
            {
                $preferredXCoord += $previousSibling->getMarginRight() + $siblingEndX - $parentX;
                $preferredYCoord = $siblingStartY + $previousSibling->getMarginTop() - $glyph->getMarginTop();
                if($previousSibling instanceof Glyphs\Text)
                {
                    $preferredYCoord -= $previousSibling->getLineHeight() * (count($previousSibling->getLineSizes()) - 1);
                }
            }
            else
            {
                $preferredYCoord = $siblingEndY - ($previousSibling->getMarginBottom() + $glyph->getMarginTop());
            }
        }
    }

    private function isGlyphInSameRowAsPreviousSibling(Glyph $glyph, Glyph $previousSibling)
    {
        $oneOfGlyphsIsInline = $previousSibling->getAttribute('display') === Glyphs\AbstractGlyph::DISPLAY_INLINE && $glyph->getDisplay() === Glyphs\AbstractGlyph::DISPLAY_INLINE;

        $parent = $glyph->getParent();
        $parentBoundary = $parent->getBoundary();

        list($prevX) = $previousSibling->getEndDrawingPoint();
        $endX = $prevX + $previousSibling->getMarginRight() + $glyph->getMarginLeft() + $glyph->getWidth();
        $parentEndX = $parentBoundary->getFirstPoint()->getX() + $parent->getWidth();

        $rowIsOverflowed = !$glyph instanceof Glyphs\Text && $parentEndX < $endX && $previousSibling->getFloat() !== Glyphs\AbstractGlyph::FLOAT_RIGHT;

        return !$rowIsOverflowed && $oneOfGlyphsIsInline;
    }
}