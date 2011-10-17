<?php

namespace PHPPdf\Stub\ComplexAttribute;

use PHPPdf\Document;
use PHPPdf\ComplexAttribute\ComplexAttribute,
    PHPPdf\Node\Page,
    PHPPdf\Node\Node;

class ComplexAttributeStub extends ComplexAttribute
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