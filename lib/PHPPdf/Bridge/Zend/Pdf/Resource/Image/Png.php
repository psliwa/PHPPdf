<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Bridge\Zend\Pdf\Resource\Image;

use PHPPdf\InputStream\FopenInputStream;
use PHPPdf\InputStream\StringInputStream;
use ZendPdf\Resource\Image\Png as BasePng;
use ZendPdf\Exception;
use ZendPdf;
use ZendPdf\ObjectFactory;
use ZendPdf\InternalType;

/**
 * Content loading type has been changed, remote files are supported.
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Png extends BasePng
{
    private $stream;
    
    const PREDICATOR = 10;
    
    public function __construct($imageFileName)
    {
        $isRemote = stripos($imageFileName, 'http') === 0;
        
        if (($this->stream = $this->open($isRemote, $imageFileName)) === false ) {
            
            throw new Exception\IOException("Can not open '$imageFileName' file for reading.");
        }

        \ZendPdf\Resource\Image\AbstractImage::__construct();
        
        //Check if the file is a PNG
        $this->seek(1);
        if ('PNG' != $this->read(3)) {
            
            throw new Exception\DomainException('Image is not a PNG');
        }
        $this->seek(12); //Signature bytes (Includes the IHDR chunk) IHDR processed linerarly because it doesnt contain a variable chunk length
        $wtmp = unpack('Ni',$this->read(4)); //Unpack a 4-Byte Long
        $width = $wtmp['i'];
        $htmp = unpack('Ni',$this->read(4));
        $height = $htmp['i'];
        $bits = ord($this->read(1)); //Higher than 8 bit depths are only supported in later versions of PDF.
        $color = ord($this->read(1));

        $compression = ord($this->read(1));
        $prefilter = ord($this->read(1));

        if (($interlacing = ord($this->read(1))) != self::PNG_INTERLACING_DISABLED) {
            throw new Exception\NotImplementedException('Only non-interlaced images are currently supported.');
        }

        $this->_width = $width;
        $this->_height = $height;
        $this->_imageProperties = array();
        $this->_imageProperties['bitDepth'] = $bits;
        $this->_imageProperties['pngColorType'] = $color;
        $this->_imageProperties['pngFilterType'] = $prefilter;
        $this->_imageProperties['pngCompressionType'] = $compression;
        $this->_imageProperties['pngInterlacingType'] = $interlacing;

        $this->seek(4); //4 Byte Ending Sequence
        $imageData = '';

        /*
         * The following loop processes PNG chunks. 4 Byte Longs are packed first give the chunk length
         * followed by the chunk signature, a four byte code. IDAT and IEND are manditory in any PNG.
         */
        while(($chunkLengthBytes = $this->read(4)) !== false) {
            $chunkLengthtmp         = unpack('Ni', $chunkLengthBytes);
            $chunkLength            = $chunkLengthtmp['i'];
            $chunkType                      = $this->read(4);
            switch($chunkType) {
                case 'IDAT': //Image Data
                    /*
                     * Reads the actual image data from the PNG file. Since we know at this point that the compression
                     * strategy is the default strategy, we also know that this data is Zip compressed. We will either copy
                     * the data directly to the PDF and provide the correct FlateDecode predictor, or decompress the data
                     * decode the filters and output the data as a raw pixel map.
                     */
                    $imageData .= $this->read($chunkLength);
                    $this->seek(4);
                    break;

                case 'PLTE': //Palette
                    $paletteData = $this->read($chunkLength);
                    $this->seek(4);
                    break;

                case 'tRNS': //Basic (non-alpha channel) transparency.
                    $trnsData = $this->read($chunkLength);
                    switch ($color) {
                        case self::PNG_CHANNEL_GRAY:
                            $baseColor = ord(substr($trnsData, 1, 1));
                            $transparencyData = array(new InternalType\NumericObject($baseColor),
                                                      new InternalType\NumericObject($baseColor));
                            break;

                        case self::PNG_CHANNEL_RGB:
                            $red = ord(substr($trnsData,1,1));
                            $green = ord(substr($trnsData,3,1));
                            $blue = ord(substr($trnsData,5,1));
                            $transparencyData = array(new InternalType\NumericObject($red),
                                                      new InternalType\NumericObject($red),
                                                      new InternalType\NumericObject($green),
                                                      new InternalType\NumericObject($green),
                                                      new InternalType\NumericObject($blue),
                                                      new InternalType\NumericObject($blue));
                            break;

                        case self::PNG_CHANNEL_INDEXED:
                            //Find the first transparent color in the index, we will mask that. (This is a bit of a hack. This should be a SMask and mask all entries values).
                            if(($trnsIdx = strpos($trnsData, "\0")) !== false) {
                                $transparencyData = array(new InternalType\NumericObject($trnsIdx),
                                                          new InternalType\NumericObject($trnsIdx));
                            }
                            break;

                        case self::PNG_CHANNEL_GRAY_ALPHA:
                            // Fall through to the next case

                        case self::PNG_CHANNEL_RGB_ALPHA:
                            
                            throw new Exception\CorruptedImageException("tRNS chunk illegal for Alpha Channel Images");
                            break;
                    }
                    $this->seek(4); //4 Byte Ending Sequence
                    break;

                case 'IEND';
                    break 2; //End the loop too

                default:
                    $this->seek($chunkLength + 4); //Skip the section
                    break;
            }
        }
        
        $this->close();

        $compressed = true;
        $imageDataTmp = '';
        $smaskData = '';
        switch ($color) {
            case self::PNG_CHANNEL_RGB:
                $colorSpace = new InternalType\NameObject('DeviceRGB');
                break;

            case self::PNG_CHANNEL_GRAY:
                $colorSpace = new InternalType\NameObject('DeviceGray');
                break;

            case self::PNG_CHANNEL_INDEXED:
                if(empty($paletteData)) {
                    throw new Exception\CorruptedImageException("PNG Corruption: No palette data read for indexed type PNG.");
                }
                $colorSpace = new InternalType\ArrayObject();
                $colorSpace->items[] = new InternalType\NameObject('Indexed');
                $colorSpace->items[] = new InternalType\NameObject('DeviceRGB');
                $colorSpace->items[] = new InternalType\NumericObject((strlen($paletteData)/3-1));
                $paletteObject = $this->_objectFactory->newObject(new InternalType\BinaryStringObject($paletteData));
                $colorSpace->items[] = $paletteObject;
                break;

            case self::PNG_CHANNEL_GRAY_ALPHA:
                /*
                 * To decode PNG's with alpha data we must create two images from one. One image will contain the Gray data
                 * the other will contain the Gray transparency overlay data. The former will become the object data and the latter
                 * will become the Shadow Mask (SMask).
                 */
                if($bits > 8) {
                    throw new Exception\NotImplementedException('Alpha PNGs with bit depth > 8 are not yet supported');
                }

                $colorSpace = new InternalType\NameObject('DeviceGray');

                $pngDataRawDecoded = $this->decode($imageData, $width, 2, $bits);

                //Iterate every pixel and copy out gray data and alpha channel (this will be slow)
                for($pixel = 0, $pixelcount = ($width * $height); $pixel < $pixelcount; $pixel++) {
                    $imageDataTmp .= $pngDataRawDecoded[($pixel*2)];
                    $smaskData .= $pngDataRawDecoded[($pixel*2)+1];
                }
                $compressed = false;
                $imageData  = $imageDataTmp; //Overwrite image data with the gray channel without alpha
                break;

            case self::PNG_CHANNEL_RGB_ALPHA:
                /*
                 * To decode PNG's with alpha data we must create two images from one. One image will contain the RGB data
                 * the other will contain the Gray transparency overlay data. The former will become the object data and the latter
                 * will become the Shadow Mask (SMask).
                 */
                if($bits > 8) {
                    throw new Exception\NotImplementedException('Alpha PNGs with bit depth > 8 are not yet supported');
                }

                $colorSpace = new InternalType\NameObject('DeviceRGB');

                $pngDataRawDecoded = $this->decode($imageData, $width, 4, $bits);

                //Iterate every pixel and copy out rgb data and alpha channel (this will be slow)
                for($pixel = 0, $pixelcount = ($width * $height); $pixel < $pixelcount; $pixel++) {
                    $imageDataTmp .= $pngDataRawDecoded[($pixel*4)+0] . $pngDataRawDecoded[($pixel*4)+1] . $pngDataRawDecoded[($pixel*4)+2];
                    $smaskData .= $pngDataRawDecoded[($pixel*4)+3];
                }

                $compressed = false;
                $imageData  = $imageDataTmp; //Overwrite image data with the RGB channel without alpha
                break;

            default:
                throw new Exception\CorruptedImageException('PNG Corruption: Invalid color space.');
        }

        if(empty($imageData)) {
            throw new Exception\CorruptedImageException('Corrupt PNG Image. Mandatory IDAT chunk not found.');
        }

        $imageDictionary = $this->_resource->dictionary;
        if(!empty($smaskData)) {
            /*
             * Includes the Alpha transparency data as a Gray Image, then assigns the image as the Shadow Mask for the main image data.
             */
            $smaskStream = $this->_objectFactory->newStreamObject($smaskData);
            $smaskStream->dictionary->Type             = new InternalType\NameObject('XObject');
            $smaskStream->dictionary->Subtype          = new InternalType\NameObject('Image');
            $smaskStream->dictionary->Width            = new InternalType\NumericObject($width);
            $smaskStream->dictionary->Height           = new InternalType\NumericObject($height);
            $smaskStream->dictionary->ColorSpace       = new InternalType\NameObject('DeviceGray');
            $smaskStream->dictionary->BitsPerComponent = new InternalType\NumericObject($bits);
            $imageDictionary->SMask = $smaskStream;

            // Encode stream with FlateDecode filter
            $smaskStreamDecodeParms = array();
            $smaskStreamDecodeParms['Predictor']        = new InternalType\NumericObject(self::PREDICATOR);
            $smaskStreamDecodeParms['Columns']          = new InternalType\NumericObject($width);
            $smaskStreamDecodeParms['Colors']           = new InternalType\NumericObject(1);
            $smaskStreamDecodeParms['BitsPerComponent'] = new InternalType\NumericObject(8);
            $smaskStream->dictionary->DecodeParms  = new InternalType\DictionaryObject($smaskStreamDecodeParms);
            $smaskStream->dictionary->Filter       = new InternalType\NameObject('FlateDecode');
        }

        if(!empty($transparencyData)) {
            //This is experimental and not properly tested.
            $imageDictionary->Mask = new InternalType\ArrayObject($transparencyData);
        }

        $imageDictionary->Width            = new InternalType\NumericObject($width);
        $imageDictionary->Height           = new InternalType\NumericObject($height);
        $imageDictionary->ColorSpace       = $colorSpace;
        $imageDictionary->BitsPerComponent = new InternalType\NumericObject($bits);
        $imageDictionary->Filter       = new InternalType\NameObject('FlateDecode');

        $decodeParms = array();
        $decodeParms['Predictor']        = new InternalType\NumericObject(self::PREDICATOR); // Optimal prediction
        $decodeParms['Columns']          = new InternalType\NumericObject($width);
        $decodeParms['Colors']           = new InternalType\NumericObject((($color==self::PNG_CHANNEL_RGB || $color==self::PNG_CHANNEL_RGB_ALPHA)?(3):(1)));
        $decodeParms['BitsPerComponent'] = new InternalType\NumericObject($bits);
        $imageDictionary->DecodeParms  = new InternalType\DictionaryObject($decodeParms);

        //Include only the image IDAT section data.
        $this->_resource->value = $imageData;

        //Skip double compression
        if ($compressed) {
            $this->_resource->skipFilters();
        }
    }
    
    private function decode($imageData, $width, $colors, $bits)
    {
        $decodingObjFactory = ObjectFactory::createFactory(1);
        $decodingStream = $decodingObjFactory->newStreamObject($imageData);
        $decodingStream->dictionary->Filter      = new InternalType\NameObject('FlateDecode');
        $decodingStream->dictionary->DecodeParms = new InternalType\DictionaryObject();
        $decodingStream->dictionary->DecodeParms->Predictor        = new InternalType\NumericObject(self::PREDICATOR);
        $decodingStream->dictionary->DecodeParms->Columns          = new InternalType\NumericObject($width);
        $decodingStream->dictionary->DecodeParms->Colors           = new InternalType\NumericObject($colors);
        $decodingStream->dictionary->DecodeParms->BitsPerComponent = new InternalType\NumericObject($bits);
        $decodingStream->skipFilters();

        return $decodingStream->value;
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
    
    private function seek($index)
    {
        $this->stream->seek($index);
    }
    
    private function read($length)
    {
        return $this->stream->read($length);
    }
    
    private function close()
    {
        $this->stream->close();
        $this->stream = null;
    }
}