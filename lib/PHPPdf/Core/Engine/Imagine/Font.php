<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Engine\Imagine;

use Imagine\Image\ImagineInterface;
use PHPPdf\Core\Engine\AbstractFont;
use Imagine\Image\Color as ImagineColor;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Font extends AbstractFont
{
    /**
     * @var Imagine
     */
    private $imagine;
    
    public function __construct(array $fontResources, ImagineInterface $imagine)
    {
        parent::__construct($fontResources);

        $this->imagine = $imagine;
    }

    public function getWidthOfText($text, $fontSize)
    {
        $color = $this->createColor('#000000');

        $font = $this->imagine->font($this->fontResources[$this->currentStyle], $fontSize, $color);
        
        try
        {
            $box = $font->box($text);
            
            return $box->getWidth();
        }
        catch(\InvalidArgumentException $e)
        {
            return 0;
        }
    }
    
    private function createColor($color)
    {
        if(!$color instanceof ImagineColor)
        {
            $color = new ImagineColor($color);
        }

        return $color;
    }
    
    /**
     * @internal
     */
    public function getWrappedFont($color, $fontSize)
    {
        $color = $this->createColor($color);
        return $this->imagine->font($this->fontResources[$this->currentStyle], $fontSize, $color);
    }
    
    public function getCurrentResourceIdentifier()
    {
        return $this->fontResources[$this->currentStyle];
    }
}