<?php

namespace PHPPdf\Formatter;

use PHPPdf\Glyph as Glyphs,
    PHPPdf\Document,
    PHPPdf\Util\Point;

/**
 * Calculates text's real dimension
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class TextResetPositionFormatter extends BaseFormatter
{
    public function format(Glyphs\Glyph $glyph, Document $document)
    {
        $boundary = $glyph->getBoundary();
        list($x, $y) = $glyph->getFirstPoint()->toArray();
        $boundary->reset();

        $boundary->setNext($x, $y);
    }
}