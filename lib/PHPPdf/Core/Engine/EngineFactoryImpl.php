<?php

namespace PHPPdf\Core\Engine;

use PHPPdf\Exception\DomainException;

use PHPPdf\Core\Engine\Imagine\Engine as ImagineEngine;
use PHPPdf\Core\Engine\ZF\Engine as ZendEngine;

class EngineFactoryImpl implements EngineFactory
{
    const TYPE_PDF = 'pdf';
    const TYPE_IMAGE = 'image';
    
    const OPTION_ENGINE = 'engine';
    const OPTION_FORMAT = 'format';
    
    const ENGINE_GD = 'Gd';
    const ENGINE_IMAGICK = 'Imagick';
    const ENGINE_GMAGICK = 'Gmagick';
    
    const FORMAT_PNG = 'png';
    const FORMAT_JPEG = 'jpeg';

    public function createEngine($type, array $options = array())
    {
        switch($type)
        {
            case self::TYPE_PDF:
                return new ZendEngine();
            case self::TYPE_IMAGE:
                $engine = $this->getOption(self::OPTION_ENGINE, $options, self::ENGINE_GD);
                $format = $this->getOption(self::OPTION_FORMAT, $options, self::FORMAT_JPEG);

                $imagineClass = sprintf('Imagine\%s\Imagine', $engine);   

                if(!class_exists($imagineClass, true))
                {
                    throw new DomainException(sprintf('Unknown image engine type "%s" or Imagine library is not installed.', $engine));
                }
                
                $imagine = new $imagineClass();
                
                return new ImagineEngine($imagine, $format);
            default:
                throw new DomainException(sprintf('Unknown engine type: "%s".', $type));
        }
    }
    
    private function getOption($name, array $options, $default = null)
    {
        return isset($options[$name]) ? $options[$name] : $default;
    }
}