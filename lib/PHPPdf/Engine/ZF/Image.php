<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Engine\ZF;

use PHPPdf\Exception\InvalidResourceException,
    PHPPdf\Bridge\Zend\Pdf\Resource\Image\Png,
    PHPPdf\Engine\Image as BaseImage;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Image implements BaseImage
{
    private $zendImage;
    
    public function __construct($path)
    {
        try
        {            
            if(!$this->pathExists($path) || ($data = @getimagesize($path)) === false)
            {
                InvalidResourceException::invalidImageException($path);
            }
            
            $imageType = $data[2];
            
            if($imageType === IMAGETYPE_JPEG || $imageType === IMAGETYPE_JPEG2000)
            {
                $this->zendImage = new \Zend_Pdf_Resource_Image_Jpeg($path);
            }
            elseif($imageType === IMAGETYPE_PNG)
            {
                $this->zendImage = new Png($path);
            }
            elseif($imageType === IMAGETYPE_TIFF_II || $imageType === IMAGETYPE_TIFF_MM)
            {
                $this->zendImage = new \Zend_Pdf_Resource_Image_Tiff($path);
            }
            else
            {
                InvalidResourceException::unsupportetImageTypeException($path);
            }
        }
        catch(\Zend_Pdf_Exception $e)
        {
            InvalidResourceException::invalidImageException($path, $e);
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
        return $this->zendImage->getPixelHeight();
    }
    
    public function getOriginalWidth()
    {
        return $this->zendImage->getPixelWidth();
    }
    
    /**
     * @internal Public method within PHPPdf\Engine\ZF namespace
     * 
     * @return Zend_Pdf_Resource_Image
     */
    public function getWrappedImage()
    {
        return $this->zendImage;
    }
}