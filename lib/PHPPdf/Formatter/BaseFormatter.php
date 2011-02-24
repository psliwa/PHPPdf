<?php

namespace PHPPdf\Formatter;

use PHPPdf\Formatter\Formatter,
    PHPPdf\Glyph\Glyph,
    PHPPdf\Document,
    PHPPdf\Formatter\Chain;

/**
 * Base formatter class
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
abstract class BaseFormatter implements Formatter
{
    private $document;

    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    public function getDocument()
    {
        return $this->document;
    }

    public function preFormat(Glyph $glyph)
    {
    }

    public function postFormat(Glyph $glyph)
    {
    }
}