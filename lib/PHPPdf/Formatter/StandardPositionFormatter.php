<?php

namespace PHPPdf\Formatter;

use PHPPdf\Glyph as Glyphs,
    PHPPdf\Util\Boundary;

/**
 * Calculates real position of glyph
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class StandardPositionFormatter extends BaseFormatter
{
    public function preFormat(Glyphs\Glyph $glyph)
    {
        $glyph->makeAttributesSnapshot();
        $boundary = $glyph->getBoundary();
        if($boundary->isClosed())
        {
            return;
        }

        $page = $glyph->getPage();

        $parent = $glyph->getParent();

        list($parentX, $parentY) = $parent->getStartDrawingPoint();

        $startX = $glyph->getMarginLeft() + $parentX;
        $startY = $parentY - $glyph->getMarginTop();

        $this->setGlyphsPosition($glyph, $startX, $startY);
     
        $boundary->setNext($startX, $startY);
    }

    private function setGlyphsPosition(Glyphs\Glyph $glyph, &$preferredXCoord, &$preferredYCoord)
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

    public function postFormat(Glyphs\Glyph $glyph)
    {
        $boundary = $glyph->getBoundary();
        if(/*$glyph->getDisplay() === Glyphs\AbstractGlyph::DISPLAY_BLOCK &&*/ !$boundary->isClosed())
        {
            list($x, $y) = $boundary->getFirstPoint()->toArray();

            $attributesSnapshot = $glyph->getAttributesSnapshot();
            $diffWidth = $glyph->getWidth() - $attributesSnapshot['width'];
            $width = $glyph->getWidth();
            $x += $width;
            $yEnd = $y - $glyph->getHeight();
            $boundary->setNext($x, $y)
                     ->setNext($x, $yEnd)
                     ->setNext($x - $width, $yEnd)
                     ->close();

            if($glyph->hadAutoMargins())
            {
                $glyph->translate(-$diffWidth/2, 0);
            }
        }
    }

    private function isGlyphInSameRowAsPreviousSibling(Glyphs\Glyph $glyph, Glyphs\Glyph $previousSibling)
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