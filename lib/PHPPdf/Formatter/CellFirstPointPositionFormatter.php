<?php

namespace PHPPdf\Formatter;

use PHPPdf\Glyph\Glyph,
    PHPPdf\Document;

class CellFirstPointPositionFormatter extends BaseFormatter
{
    public function format(Glyph $glyph, Document $document)
    {
        $parent = $glyph->getParent();
        $boundary = $glyph->getBoundary();

        $boundary->setNext($parent->getFirstPoint());
    }
}