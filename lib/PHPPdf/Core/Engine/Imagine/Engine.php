<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Engine\Imagine;

use Imagine\Image\Color;
use Imagine\Image\ImagineInterface;
use PHPPdf\Core\Engine\Engine as BaseEngine;
use PHPPdf\Core\Engine\GraphicsContext as BaseGraphicsContext;

/**
 * Engine implementation for Imagine library
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Engine implements BaseEngine
{
    private $imagine;
    private $graphicsContexts = array();
    
    public function __construct(ImagineInterface $imagine)
    {
        $this->imagine = $imagine;
    }
    
    public function createGraphicsContext($graphicsContextSize)
    {
        return new GraphicsContext($this->imagine, $graphicsContextSize);
    }
    
    public function createColor($data)
    {
        return new Color($color);
    }
    
    public function createImage($imageData)
    {
        return new Image($imageData, $this->imagine);
    }
    
    public function createFont($fontData)
    {
        return new Font($fontData, $this->imagine);
    }
    
    public function attachGraphicsContext(BaseGraphicsContext $gc)
    {
        $this->graphicsContexts[] = $gc;
    }
    
    public function getAttachedGraphicsContexts()
    {
        return $this->graphicsContexts;
    }
    
    public function render()
    {
        $contents = array();

        foreach($this->graphicsContexts as $gc)
        {
            $gc->commit();
            $contents[] = $gc->render();
        }

        return $contents;
    }
    
    public function loadEngine($file)
    {
        
    }
    
    public function setMetadataValue($name, $value)
    {
        
    }
}