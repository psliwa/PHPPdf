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
        $this->imagine = $imagine;
        if($imagePath instanceof ImageInterface)
        {
            $this->image = $imagePath;
        }
        else
        {
            $this->image = $this->createImage($imagePath);
        }
    }
    
    private function createImage($path)
    {
        try
        {
            return $this->imagine->open($path);
        }
        catch(\Imagine\Exception\Exception $e)
        {
            throw InvalidResourceException::invalidImageException($path, $e);
        }
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
        return $this->image;
    }
    
    public function getOriginalHeight()
    {
        return $this->getWrappedImage()->getSize()->getHeight();
    }
}