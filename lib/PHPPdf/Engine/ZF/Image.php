<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Engine\ZF;

use PHPPdf\Exception\InvalidResourceException,
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
            $this->zendImage = \Zend_Pdf_Image::imageWithPath($path);
        }
        catch(\Zend_Pdf_Exception $e)
        {
            InvalidResourceException::invalidImageException($path, $e);
        }
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