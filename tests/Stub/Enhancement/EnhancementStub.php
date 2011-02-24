<?php

use PHPPdf\Enhancement\Enhancement,
    PHPPdf\Glyph\Page,
    PHPPdf\Glyph\Glyph;

class EnhancementStub extends Enhancement
{
    public function __construct($color, $someParameter = null)
    {
    }

    protected function doEnhance(Page $page, Glyph $glyph)
    {
    }
}