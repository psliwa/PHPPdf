<?php

namespace PHPPdf\Formatter;

use PHPPdf\Formatter\BaseFormatter,
    PHPPdf\Glyph as Glyphs,
    PHPPdf\Formatter\Chain;

/**
 * Collect debug information
 *
 * @todo implementation
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class DebugFormatter extends BaseFormatter
{
    public function preFormat(Glyphs\Glyph $glyph)
    {
        //@todo
    }

    public function postFormat(Glyphs\Glyph $glyph)
    {
        //@todo
    }
}