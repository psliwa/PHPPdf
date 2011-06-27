<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Formatter;

use PHPPdf\Formatter\Formatter,
    PHPPdf\Glyph as Glyphs,
    PHPPdf\Formatter\Chain;

/**
 * Sets chain to children glyphs
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ContainerFormatter extends BaseFormatter
{
    public function format(Glyphs\Glyph $glyph, \PHPPdf\Document $document)
    {
        foreach($glyph->getChildren() as $child)
        {
            $child->preFormat($document);
            $child->format($document);
        }
    }
}