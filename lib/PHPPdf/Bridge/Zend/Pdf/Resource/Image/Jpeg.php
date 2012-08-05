<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Bridge\Zend\Pdf\Resource\Image;

use PHPPdf\InputStream\FopenInputStream;
use PHPPdf\InputStream\StringInputStream;
use ZendPdf\Resource\Image\AbstractImage;
use ZendPdf\Resource\Image\Jpeg as BaseJpeg;
use ZendPdf\InternalType;
use ZendPdf\Exception;

/**
 * Content loading type has been changed, remote files are supported.
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Jpeg extends BaseJpeg
{
    public function __construct($imageFileName)
    {
        if (!function_exists('gd_info')) {
            throw new Exception\RuntimeException('Image extension is not installed.');
        }

        $gd_options = gd_info();
        if ( (!isset($gd_options['JPG Support'])  || $gd_options['JPG Support']  != true)  &&
             (!isset($gd_options['JPEG Support']) || $gd_options['JPEG Support'] != true)  ) {
            throw new Exception\RuntimeException('JPG support is not configured properly.');
        }

        if (($imageInfo = getimagesize($imageFileName)) === false) {
            throw new Exception\CorruptedImageException('Corrupted image.');
        }
        if ($imageInfo[2] != IMAGETYPE_JPEG && $imageInfo[2] != IMAGETYPE_JPEG2000) {
            throw new Exception\DomainException('ImageType is not JPG');
        }

        AbstractImage::__construct();

        switch ($imageInfo['channels']) {
            case 3:
                $colorSpace = 'DeviceRGB';
                break;
            case 4:
                $colorSpace = 'DeviceCMYK';
                break;
            default:
                $colorSpace = 'DeviceGray';
                break;
        }

        $imageDictionary = $this->_resource->dictionary;
        $imageDictionary->Width            = new InternalType\NumericObject($imageInfo[0]);
        $imageDictionary->Height           = new InternalType\NumericObject($imageInfo[1]);
        $imageDictionary->ColorSpace       = new InternalType\NameObject($colorSpace);
        $imageDictionary->BitsPerComponent = new InternalType\NumericObject($imageInfo['bits']);
        if ($imageInfo[2] == IMAGETYPE_JPEG) {
            $imageDictionary->Filter       = new InternalType\NameObject('DCTDecode');
        } else if ($imageInfo[2] == IMAGETYPE_JPEG2000){
            $imageDictionary->Filter       = new InternalType\NameObject('JPXDecode');
        }

        $isRemote = stripos($imageFileName, 'http') === 0;
       
        if (($stream = $this->open($isRemote, $imageFileName)) === false ) {
            throw new Exception\IOException("Can not open '$imageFileName' file for reading.");
        }
        $byteCount = $stream->size();
        $this->_resource->value = '';
        while ( $byteCount > 0 && ($nextBlock = $stream->read($byteCount)) != false ) {
            $this->_resource->value .= $nextBlock;
            $byteCount -= strlen($nextBlock);
        }
        $stream->close();

        $this->_resource->skipFilters();

        $this->_width = $imageInfo[0];
        $this->_height = $imageInfo[1];
        $this->_imageProperties = array();
        $this->_imageProperties['bitDepth'] = $imageInfo['bits'];
        $this->_imageProperties['jpegImageType'] = $imageInfo[2];
        $this->_imageProperties['jpegColorType'] = $imageInfo['channels'];
    }
    
    private function open($isRemote, $imageFileName)
    {
        try 
        {
            if($isRemote)
            {
                $content = @file_get_contents($imageFileName);
                
                if($content === false)
                {
                    return false;
                }
                
                return new StringInputStream($content);
            }
            else
            {
                return new FopenInputStream($imageFileName, 'rb');
            }
        }
        catch(\PHPPdf\Exception $e)
        {
            return false;
        }
    }
}