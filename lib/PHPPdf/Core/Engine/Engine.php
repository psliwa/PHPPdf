<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Engine;

use PHPPdf\Core\UnitConverter;

/**
 * Engine is an Abstract Factory for objects related with graphics engine type
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface Engine extends UnitConverter
{
    /**
     * Creates and returns GraphicsContext. 
     * 
     * Returned graphics context isn't attached to engine, 
     * {@see attachGraphicsContext()} have to been invoked.
     * 
     * @return GraphicsContext
     */
    public function createGraphicsContext($graphicsContextSize, $encoding);
    
    /**
     * Creates and returns Image object depends on image data
     * 
     * @return Image
     */
    public function createImage($imageData);
    
    /**
     * Creates and returns Font object depends on font data
     * 
     * @return Font
     */
    public function createFont($fontData);
    
    /**
     * Attachs GraphicsContext to engine
     */
    public function attachGraphicsContext(GraphicsContext $gc);
    
    /**
     * @return array All of attached graphics contexts
     */
    public function getAttachedGraphicsContexts();
    
    /**
     * Renders document
     * 
     * @return string String representation of the document
     */
    public function render();
    
    /**
     * Loads Engine object from given Pdf document
     * 
     * @return Engine
     */
    public function loadEngine($file, $encoding);
    
    /**
     * Sets single metadata value for document
     * 
     * @param string $name Name of metadata
     * @param mixed $value Value of metadata
     */
    public function setMetadataValue($name, $value);
    
    public function reset();
}