<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Engine\Imagine;

use PHPPdf\Exception\InvalidResourceException;
use PHPPdf\Core\Engine\AbstractEngine;
use PHPPdf\Core\UnitConverter;
use Imagine\Image\ImagineInterface;
use PHPPdf\Core\Engine\Engine as BaseEngine;
use PHPPdf\Core\Engine\GraphicsContext as BaseGraphicsContext;

/**
 * Engine implementation for Imagine library
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Engine extends AbstractEngine
{
    private $imagine;
    private $graphicsContexts = array();
    private $outputFormat;
    private $renderOptions;
    
    public function __construct(ImagineInterface $imagine, $outputFormat, UnitConverter $unitConverter = null, array $renderOptions = array())
    {
        parent::__construct($unitConverter);
        $this->imagine = $imagine;
        $this->outputFormat = (string) $outputFormat;
        $this->renderOptions = $renderOptions;
    }
    
    public function createGraphicsContext($graphicsContextSize, $encoding)
    {
        return new GraphicsContext($this->imagine, $graphicsContextSize);
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
            $contents[] = $gc->render($this->outputFormat, $this->renderOptions);
        }

        return $contents;
    }
    
    public function loadEngine($file, $encoding)
    {
        try
        {
            $image = $this->imagine->open($file);
            
            $gc = new GraphicsContext($this->imagine, $image);
            
            $engine = new self($this->imagine, $this->outputFormat, $this->unitConverter);
            
            $engine->attachGraphicsContext($gc);
            
            return $engine;
        }
        catch(\Imagine\Exception\RuntimeException $e)
        {
            throw InvalidResourceException::invalidImageException($file, $e);
        }
    }

    public function setMetadataValue($name, $value)
    {
        //not supported
    }
    
    public function reset()
    {
        $this->graphicsContexts = array();
    }
}