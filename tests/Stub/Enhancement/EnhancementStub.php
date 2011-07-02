<?php

use PHPPdf\Enhancement\Enhancement,
    PHPPdf\Glyph\Page,
    PHPPdf\Glyph\Glyph;

class EnhancementStub extends Enhancement
{
    private $color;
    private $someParameter;
    
    public function __construct($color, $someParameter = null)
    {
        $this->color = $color;
        $this->someParameter = $someParameter;
    }

    protected function doEnhance(Page $page, Glyph $glyph)
    {
    }
}