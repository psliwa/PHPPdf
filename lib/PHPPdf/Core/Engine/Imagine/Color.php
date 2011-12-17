<?php

namespace PHPPdf\Core\Engine\Imagine;

use PHPPdf\Core\Engine\Color as BaseColor;
use Imagine\Image\Color as ImagineColor;

class Color implements BaseColor
{
    private $color;
    
    public function __construct(ImagineColor $imagineColor)
    {
        $this->color = $imagineColor;
    }
    
    public function getComponents()
    {
        return array($this->color->getRed(), $this->color->getGreen(), $this->color->getBlue());
    }
    
    /**
     * @return Imagine\Image\Color
     */
    public function getWrappedColor()
    {
        return $this->color;
    }
}