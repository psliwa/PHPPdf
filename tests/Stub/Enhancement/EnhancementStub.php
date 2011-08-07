<?php

use PHPPdf\Document;
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

    protected function doEnhance($gc, Glyph $glyph, Document $document)
    {
    }
}