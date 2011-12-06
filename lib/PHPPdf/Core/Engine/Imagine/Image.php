<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */


namespace PHPPdf\Core\Engine\Imagine;

use Imagine\Image\ImageInterface;
use PHPPdf\Exception\InvalidResourceException;
use Imagine\Exception\RuntimeException;
use Imagine\Image\ImagineInterface;
use PHPPdf\Core\Engine\Image as BaseImage;

/**
 * Image implementation for Imagine
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Image implements BaseImage
{
    private $imagePath;
    private $imagine;
    private $image;
    
    public function __construct($imagePath, ImagineInterface $imagine)
    {
        if($imagePath instanceof ImageInterface)
        {
            $this->image = $imagePath;
        }
        else
        {
            $this->imagePath = $imagePath;
        }
        $this->imagine = $imagine;
    }
    
    public function getOriginalWidth()
    {
        return $this->getWrappedImage()->getSize()->getWidth();
    }
    
    /**
     * @internal
     */
    public function getWrappedImage()
    {
        if($this->image === null)
        {
            try
            {
                $this->image = $this->imagine->open($this->imagePath);
            }
            catch(\Imagine\Exception\Exception $e)
            {
                InvalidResourceException::invalidImageException($this->imagePath, $e);
            }
        }
        
        return $this->image;
    }
    
    public function getOriginalHeight()
    {
        return $this->getWrappedImage()->getSize()->getHeight();
    }
}