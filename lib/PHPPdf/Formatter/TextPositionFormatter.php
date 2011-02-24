<?php

namespace PHPPdf\Formatter;

use PHPPdf\Formatter\BaseFormatter,
    PHPPdf\Glyph as Glyphs,
    PHPPdf\Formatter\Chain,
    PHPPdf\Util\Point;

/**
 * Calculates text's real dimension
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class TextPositionFormatter extends BaseFormatter
{
    public function preFormat(Glyphs\Glyph $glyph)
    {
        if($this->isInlineText($glyph))
        {
            $boundary = $glyph->getBoundary();
            list($x, $y) = $boundary->getFirstPoint()->toArray();
            $boundary->reset();

            $boundary->setNext($x, $y);
        }
    }

    private function isInlineText(Glyphs\Glyph $glyph)
    {
        return ($glyph instanceof Glyphs\Text && $glyph->getDisplay() === Glyphs\AbstractGlyph::DISPLAY_INLINE);
    }

    public function postFormat(Glyphs\Glyph $glyph)
    {
        if($this->isInlineText($glyph))
        {
            $boundary = $glyph->getBoundary();

            list($x, $y) = $boundary->getFirstPoint()->toArray();
            list($parentX, $parentY) = $glyph->getParent()->getStartDrawingPoint();

            $lineSizes = $glyph->getLineSizes();
            $lineHeight = $glyph->getLineHeight();

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
}