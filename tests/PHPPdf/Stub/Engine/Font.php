<?php

namespace PHPPdf\Stub\Engine;

use PHPPdf\Core\Engine\AbstractFont;

class Font extends AbstractFont
{
    public function getWidthOfText($text, $fontSize)
    {
        return 0;
    }
    
    public function getCurrentResourceIdentifier()
    {
        return 'abc';
    }
}