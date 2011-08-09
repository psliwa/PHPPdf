<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
final class GlyphWrapper implements GlyphAware
{
    private $glyph;
    
    public function __construct(Glyph $glyph = null)
    {
        $this->glyph = $glyph;
    }
    
    public function getGlyph()
    {
        return $this->glyph;
    }
    
    public function setGlyph(Glyph $glyph)
    {
        $this->glyph = $glyph;
    }
}