<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Formatter;

use PHPPdf\Glyph as Glyphs,
    PHPPdf\Document,
    PHPPdf\Util\Boundary;

/**
 * Calculates real position of glyph
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class StandardPositionFormatter extends BaseFormatter
{
    public function format(Glyphs\Glyph $glyph, Document $document)
    {
        $boundary = $glyph->getBoundary();
        if(!$boundary->isClosed())
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
}