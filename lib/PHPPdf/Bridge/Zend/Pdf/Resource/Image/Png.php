<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Bridge\Zend\Pdf\Resource\Image;

/**
 * Content loading type has been changed, remote files are supported.
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Png extends \Zend_Pdf_Resource_Image_Png
{
    private $currentIndex = 0;
    private $content;
    private $contentLength;
    private $isRemote;
    
    const PREDICATOR = 10;
    
    public function __construct($imageFileName)
    {
        //if file is not remote, use original constructor
        $this->isRemote = stripos($imageFileName, 'http') === 0;
        
        if (($this->content = $this->open($imageFileName)) === false ) {
            
            throw new \Zend_Pdf_Exception( "Can not open '$imageFileName' file for reading." );
        }

        \Zend_Pdf_Resource_Image::__construct();
        
        //Check if the file is a PNG
        $this->seek(1);
        if ('PNG' != $this->read(3)) {
            
            throw new \Zend_Pdf_Exception('Image is not a PNG');
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

        if (($interlacing = ord($this->read(1))) != \Zend_Pdf_Resource_Image_Png::PNG_INTERLACING_DISABLED) {
            
            throw new \Zend_Pdf_Exception( "Only non-interlaced images are currently supported." );
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
                        case \Zend_Pdf_Resource_Image_Png::PNG_CHANNEL_GRAY:
                            $baseColor = ord(substr($trnsData, 1, 1));
                            $transparencyData = array(new \Zend_Pdf_Element_Numeric($baseColor),
                                                      new \Zend_Pdf_Element_Numeric($baseColor));
                            break;

                        case \Zend_Pdf_Resource_Image_Png::PNG_CHANNEL_RGB:
                            $red = ord(substr($trnsData,1,1));
                            $green = ord(substr($trnsData,3,1));
                            $blue = ord(substr($trnsData,5,1));
                            $transparencyData = array(new \Zend_Pdf_Element_Numeric($red),
                                                      new \Zend_Pdf_Element_Numeric($red),
                                                      new \Zend_Pdf_Element_Numeric($green),
                                                      new \Zend_Pdf_Element_Numeric($green),
                                                      new \Zend_Pdf_Element_Numeric($blue),
                                                      new \Zend_Pdf_Element_Numeric($blue));
                            break;

                        case \Zend_Pdf_Resource_Image_Png::PNG_CHANNEL_INDEXED:
                            //Find the first transparent color in the index, we will mask that. (This is a bit of a hack. This should be a SMask and mask all entries values).
                            if(($trnsIdx = strpos($trnsData, "\0")) !== false) {
                                $transparencyData = array(new \Zend_Pdf_Element_Numeric($trnsIdx),
                                                          new \Zend_Pdf_Element_Numeric($trnsIdx));
                            }
                            break;

                        case \Zend_Pdf_Resource_Image_Png::PNG_CHANNEL_GRAY_ALPHA:
                            // Fall through to the next case

                        case \Zend_Pdf_Resource_Image_Png::PNG_CHANNEL_RGB_ALPHA:
                            
                            throw new \Zend_Pdf_Exception( "tRNS chunk illegal for Alpha Channel Images" );
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
            case \Zend_Pdf_Resource_Image_Png::PNG_CHANNEL_RGB:
                $colorSpace = new \Zend_Pdf_Element_Name('DeviceRGB');
                break;

            case \Zend_Pdf_Resource_Image_Png::PNG_CHANNEL_GRAY:
                $colorSpace = new \Zend_Pdf_Element_Name('DeviceGray');
                break;

            case \Zend_Pdf_Resource_Image_Png::PNG_CHANNEL_INDEXED:
                if(empty($paletteData)) {
                    
                    throw new \Zend_Pdf_Exception( "PNG Corruption: No palette data read for indexed type PNG." );
                }
                $colorSpace = new \Zend_Pdf_Element_Array();
                $colorSpace->items[] = new \Zend_Pdf_Element_Name('Indexed');
                $colorSpace->items[] = new \Zend_Pdf_Element_Name('DeviceRGB');
                $colorSpace->items[] = new \Zend_Pdf_Element_Numeric((strlen($paletteData)/3-1));
                $paletteObject = $this->_objectFactory->newObject(new \Zend_Pdf_Element_String_Binary($paletteData));
                $colorSpace->items[] = $paletteObject;
                break;

            case \Zend_Pdf_Resource_Image_Png::PNG_CHANNEL_GRAY_ALPHA:
                /*
                 * To decode PNG's with alpha data we must create two images from one. One image will contain the Gray data
                 * the other will contain the Gray transparency overlay data. The former will become the object data and the latter
                 * will become the Shadow Mask (SMask).
                 */
                if($bits > 8) {
                    
                    throw new \Zend_Pdf_Exception("Alpha PNGs with bit depth > 8 are not yet supported");
                }

                $colorSpace = new \Zend_Pdf_Element_Name('DeviceGray');

                
                $decodingObjFactory = \Zend_Pdf_ElementFactory::createFactory(1);
                $decodingStream = $decodingObjFactory->newStreamObject($imageData);
                $decodingStream->dictionary->Filter      = new \Zend_Pdf_Element_Name('FlateDecode');
                $decodingStream->dictionary->DecodeParms = new \Zend_Pdf_Element_Dictionary();
                $decodingStream->dictionary->DecodeParms->Predictor        = new \Zend_Pdf_Element_Numeric(self::PREDICATOR);
                $decodingStream->dictionary->DecodeParms->Columns          = new \Zend_Pdf_Element_Numeric($width);
                $decodingStream->dictionary->DecodeParms->Colors           = new \Zend_Pdf_Element_Numeric(2);   //GreyAlpha
                $decodingStream->dictionary->DecodeParms->BitsPerComponent = new \Zend_Pdf_Element_Numeric($bits);
                $decodingStream->skipFilters();

                $pngDataRawDecoded = $decodingStream->value;

                //Iterate every pixel and copy out gray data and alpha channel (this will be slow)
                for($pixel = 0, $pixelcount = ($width * $height); $pixel < $pixelcount; $pixel++) {
                    $imageDataTmp .= $pngDataRawDecoded[($pixel*2)];
                    $smaskData .= $pngDataRawDecoded[($pixel*2)+1];
                }
                $compressed = false;
                $imageData  = $imageDataTmp; //Overwrite image data with the gray channel without alpha
                break;

            case \Zend_Pdf_Resource_Image_Png::PNG_CHANNEL_RGB_ALPHA:
                /*
                 * To decode PNG's with alpha data we must create two images from one. One image will contain the RGB data
                 * the other will contain the Gray transparency overlay data. The former will become the object data and the latter
                 * will become the Shadow Mask (SMask).
                 */
                if($bits > 8) {
                    
                    throw new \Zend_Pdf_Exception("Alpha PNGs with bit depth > 8 are not yet supported");
                }

                $colorSpace = new \Zend_Pdf_Element_Name('DeviceRGB');

                
                $decodingObjFactory = \Zend_Pdf_ElementFactory::createFactory(1);
                $decodingStream = $decodingObjFactory->newStreamObject($imageData);
                $decodingStream->dictionary->Filter      = new \Zend_Pdf_Element_Name('FlateDecode');
                $decodingStream->dictionary->DecodeParms = new \Zend_Pdf_Element_Dictionary();
                $decodingStream->dictionary->DecodeParms->Predictor        = new \Zend_Pdf_Element_Numeric(self::PREDICATOR);
                $decodingStream->dictionary->DecodeParms->Columns          = new \Zend_Pdf_Element_Numeric($width);
                $decodingStream->dictionary->DecodeParms->Colors           = new \Zend_Pdf_Element_Numeric(4);   //RGBA
                $decodingStream->dictionary->DecodeParms->BitsPerComponent = new \Zend_Pdf_Element_Numeric($bits);
                $decodingStream->skipFilters();

                $pngDataRawDecoded = $decodingStream->value;

                //Iterate every pixel and copy out rgb data and alpha channel (this will be slow)
                for($pixel = 0, $pixelcount = ($width * $height); $pixel < $pixelcount; $pixel++) {
                    $imageDataTmp .= $pngDataRawDecoded[($pixel*4)+0] . $pngDataRawDecoded[($pixel*4)+1] . $pngDataRawDecoded[($pixel*4)+2];
                    $smaskData .= $pngDataRawDecoded[($pixel*4)+3];
                }

                $compressed = false;
                $imageData  = $imageDataTmp; //Overwrite image data with the RGB channel without alpha
                break;

            default:
                
                throw new \Zend_Pdf_Exception( "PNG Corruption: Invalid color space." );
        }

        if(empty($imageData)) {
            
            throw new \Zend_Pdf_Exception( "Corrupt PNG Image. Mandatory IDAT chunk not found." );
        }

        $imageDictionary = $this->_resource->dictionary;
        if(!empty($smaskData)) {
            /*
             * Includes the Alpha transparency data as a Gray Image, then assigns the image as the Shadow Mask for the main image data.
             */
            $smaskStream = $this->_objectFactory->newStreamObject($smaskData);
            $smaskStream->dictionary->Type             = new \Zend_Pdf_Element_Name('XObject');
            $smaskStream->dictionary->Subtype          = new \Zend_Pdf_Element_Name('Image');
            $smaskStream->dictionary->Width            = new \Zend_Pdf_Element_Numeric($width);
            $smaskStream->dictionary->Height           = new \Zend_Pdf_Element_Numeric($height);
            $smaskStream->dictionary->ColorSpace       = new \Zend_Pdf_Element_Name('DeviceGray');
            $smaskStream->dictionary->BitsPerComponent = new \Zend_Pdf_Element_Numeric($bits);
            $imageDictionary->SMask = $smaskStream;

            // Encode stream with FlateDecode filter
            $smaskStreamDecodeParms = array();
            $smaskStreamDecodeParms['Predictor']        = new \Zend_Pdf_Element_Numeric(self::PREDICATOR);
            $smaskStreamDecodeParms['Columns']          = new \Zend_Pdf_Element_Numeric($width);
            $smaskStreamDecodeParms['Colors']           = new \Zend_Pdf_Element_Numeric(1);
            $smaskStreamDecodeParms['BitsPerComponent'] = new \Zend_Pdf_Element_Numeric(8);
            $smaskStream->dictionary->DecodeParms  = new \Zend_Pdf_Element_Dictionary($smaskStreamDecodeParms);
            $smaskStream->dictionary->Filter       = new \Zend_Pdf_Element_Name('FlateDecode');
        }

        if(!empty($transparencyData)) {
            //This is experimental and not properly tested.
            $imageDictionary->Mask = new \Zend_Pdf_Element_Array($transparencyData);
        }

        $imageDictionary->Width            = new \Zend_Pdf_Element_Numeric($width);
        $imageDictionary->Height           = new \Zend_Pdf_Element_Numeric($height);
        $imageDictionary->ColorSpace       = $colorSpace;
        $imageDictionary->BitsPerComponent = new \Zend_Pdf_Element_Numeric($bits);
        $imageDictionary->Filter       = new \Zend_Pdf_Element_Name('FlateDecode');

        $decodeParms = array();
        $decodeParms['Predictor']        = new \Zend_Pdf_Element_Numeric(self::PREDICATOR); // Optimal prediction
        $decodeParms['Columns']          = new \Zend_Pdf_Element_Numeric($width);
        $decodeParms['Colors']           = new \Zend_Pdf_Element_Numeric((($color==\Zend_Pdf_Resource_Image_Png::PNG_CHANNEL_RGB || $color==\Zend_Pdf_Resource_Image_Png::PNG_CHANNEL_RGB_ALPHA)?(3):(1)));
        $decodeParms['BitsPerComponent'] = new \Zend_Pdf_Element_Numeric($bits);
        $imageDictionary->DecodeParms  = new \Zend_Pdf_Element_Dictionary($decodeParms);

        //Include only the image IDAT section data.
        $this->_resource->value = $imageData;

        //Skip double compression
        if ($compressed) {
            $this->_resource->skipFilters();
        }
    }
    
    private function open($imageFileName)
    {
        if($this->isRemote)
        {
            $content = @file_get_contents($imageFileName);
            if($content !== false)
            {
                $this->contentLength = strlen($content);
            }
            
            return $content;
        }
        
        return @fopen($imageFileName, 'rb');
    }
    
    private function seek($index)
    {
        if($this->isRemote)
        {
            $this->currentIndex += $index;
        }
        else
        {
            fseek($this->content, $index, SEEK_CUR);
        }
    }
    
    private function read($length)
    {
        if($this->isRemote)
        {
            if($this->currentIndex >= $this->contentLength)
            {
                return false;
            }
            
            $last = $this->currentIndex + $length;
            
            if($last > $this->contentLength)
            {
                $last = $this->contentLength - $this->currentIndex;
            }
            
            $data = substr($this->content, $this->currentIndex, $length);
            $this->seek($length);
    
            return $data;
        }
        
        return fread($this->content, $length);
    }
    
    private function close()
    {
        if($this->isRemote)
        {
            $this->content = $this->contentLength = $this->currentIndex = null;
        }
        else 
        {
            fclose($this->content);
        }
    }
}