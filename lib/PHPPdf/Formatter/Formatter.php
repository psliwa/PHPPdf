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
 * Glyph formatter
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface Formatter
{
    public function format(Glyph $glyph, Document $document);
}