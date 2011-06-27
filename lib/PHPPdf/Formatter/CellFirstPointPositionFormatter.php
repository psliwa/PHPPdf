<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Formatter;

use PHPPdf\Glyph\Glyph,
    PHPPdf\Document;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class CellFirstPointPositionFormatter extends BaseFormatter
{
    public function format(Glyph $glyph, Document $document)
    {
        $parent = $glyph->getParent();
        $boundary = $glyph->getBoundary();

        $boundary->setNext($parent->getFirstPoint());
    }
}