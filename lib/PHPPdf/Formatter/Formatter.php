<?php

namespace PHPPdf\Formatter;

use PHPPdf\Glyph\Glyph,
    PHPPdf\Document;

/**
 * Glyph formatter
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface Formatter
{
    public function getDocument();
    public function setDocument(Document $document);
    public function preFormat(Glyph $glyph);
    public function postFormat(Glyph $glyph);
}