<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Engine\ZF;

use PHPPdf\Core\UnitConverter;
use PHPPdf\Bridge\Zend\Pdf\Resource\Image\Tiff;
use PHPPdf\Exception\InvalidResourceException;
use PHPPdf\Bridge\Zend\Pdf\Resource\Image\Png;
use PHPPdf\Core\Engine\Image as BaseImage;
use PHPPdf\Bridge\Zend\Pdf\Resource\Image\Jpeg;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Image implements BaseImage
{
    private $zendImage;
    private $path;
    private $width;
    private $height;
    private $type;
    private $unitConverter;
    
    /**
     * Constructor
     * 
     * @param string $path Path to image
     * @param UnitConverter $unitConverter Converter that converts image's size from pixels to standard unit
     */
    public function __construct($path, UnitConverter $unitConverter = null)
    {
        $this->path = $path;
        $this->unitConverter = $unitConverter;
        
        if(!$this->pathExists($path) || ($data = @getimagesize($path)) === false)
        {
            throw InvalidResourceException::invalidImageException($path);
        }
        
        $this->type = $data[2];
        $this->width = $unitConverter ? $unitConverter->convertUnit($data[0], UnitConverter::UNIT_PIXEL) : $data[0];
        $this->height = $unitConverter ? $unitConverter->convertUnit($data[1], UnitConverter::UNIT_PIXEL) : $data[1];
    }
    
    private function createImage($path)
    {
        try
        {
            $imageType = $this->type;
            
            if($imageType === IMAGETYPE_JPEG || $imageType === IMAGETYPE_JPEG2000)
            {
                return new Jpeg($path);
            }
            elseif($imageType === IMAGETYPE_PNG)
            {
                return new Png($path);
            }
            elseif($imageType === IMAGETYPE_TIFF_II || $imageType === IMAGETYPE_TIFF_MM)
            {
                return new Tiff($path);
            }
            else
            {
                throw InvalidResourceException::unsupportetImageTypeException($path);
            }
        }
        catch(\ZendPdf\Exception $e)
        {
            throw InvalidResourceException::invalidImageException($path, $e);
        }
    }
    
    private function pathExists($path)
    {
        if(is_file($path))
        {
            return true;
        }
        
        if(stripos($path, 'http') === 0)
        {
            $fp = @fopen($path, 'r');
            if($fp)
            {
                fclose($fp);
                return true;
            }
        }
        
        return false;
    }
    
    public function getOriginalHeight()
    {
        return $this->height;
    }
    
    public function getOriginalWidth()
    {
        return $this->width;
    }
    
    /**
     * @internal Public method within PHPPdf\Core\Engine\ZF namespace
     * 
     * @return ZendPdf\Resource\Image
     */
    public function getWrappedImage()
    {
        if(!$this->zendImage)
        {
            $this->zendImage = $this->createImage($this->path);
            $this->path = null;
        }

        return $this->zendImage;
    }
}