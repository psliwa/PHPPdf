<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Engine;

use PHPPdf\Core\ImageUnitConverter;
use PHPPdf\Core\PdfUnitConverter;
use PHPPdf\Exception\DomainException;
use PHPPdf\Core\Engine\Imagine\Engine as ImagineEngine;
use PHPPdf\Core\Engine\ZF\Engine as ZendEngine;

/**
 * Engine factory implementation
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class EngineFactoryImpl implements EngineFactory
{
    const TYPE_PDF = 'pdf';
    const TYPE_IMAGE = 'image';

    const OPTION_ENGINE = 'engine';
    const OPTION_FORMAT = 'format';
    const OPTION_QUALITY = 'quality';
    const OPTION_DPI = 'dpi';

    const ENGINE_GD = 'Gd';
    const ENGINE_IMAGICK = 'Imagick';
    const ENGINE_GMAGICK = 'Gmagick';
    
    const FORMAT_PNG = 'png';
    const FORMAT_JPEG = 'jpeg';
    const FORMAT_WBMP = 'wbmp';

    public function createEngine($type, array $options = array())
    {
        $dpi = isset($options[self::OPTION_DPI]) ? $options[self::OPTION_DPI] : 96;
        
        switch($type)
        {
            case self::TYPE_PDF:
                return new ZendEngine(null, new PdfUnitConverter($dpi));
            case self::TYPE_IMAGE:
                $engine = ucfirst($this->getOption(self::OPTION_ENGINE, $options, self::ENGINE_GD));
                $format = $this->getOption(self::OPTION_FORMAT, $options, self::FORMAT_JPEG);

                $imagineClass = sprintf('Imagine\%s\Imagine', $engine);   

                if(!class_exists($imagineClass, true))
                {
                    throw new DomainException(sprintf('Unknown image engine type "%s" or Imagine library is not installed.', $engine));
                }
                
                $imagine = new $imagineClass();
                
                $renderOptions = array();
                
                if(isset($options[self::OPTION_QUALITY]))
                {
                    $renderOptions[self::OPTION_QUALITY] = $options[self::OPTION_QUALITY];
                }

                return new ImagineEngine($imagine, $format, new ImageUnitConverter($dpi), $renderOptions);
            default:
                throw new DomainException(sprintf('Unknown engine type: "%s".', $type));
        }
    }
    
    private function getOption($name, array $options, $default = null)
    {
        return isset($options[$name]) ? $options[$name] : $default;
    }
}