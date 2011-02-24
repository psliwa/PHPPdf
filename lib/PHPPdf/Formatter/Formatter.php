<?php

namespace PHPPdf\Formatter;

use PHPPdf\Glyph\Glyph;

/**
 * Glyph formatter
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface Formatter
{
    public function getDocument();
    public function preFormat(Glyph $glyph);
    public function postFormat(Glyph $glyph);
}