<?php

namespace PHPPdf\Stub\Enhancement;

use PHPPdf\Document;
use PHPPdf\Enhancement\Enhancement,
    PHPPdf\Node\Page,
    PHPPdf\Node\Node;

class EnhancementStub extends Enhancement
{
    private $color;
    private $someParameter;
    
    public function __construct($color, $someParameter = null)
    {
        $this->color = $color;
        $this->someParameter = $someParameter;
    }

    protected function doEnhance($gc, Node $node, Document $document)
    {
    }
}