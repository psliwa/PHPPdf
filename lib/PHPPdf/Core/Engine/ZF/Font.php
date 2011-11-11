<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Engine\ZF;

use PHPPdf\Exception\InvalidResourceException;
use PHPPdf\Core\Engine\Font as BaseFont;
use Zend\Pdf\Font as ZendFont;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Font implements BaseFont
{
    private $fontResources = array();
    private $currentStyle = null;

    public function __construct(array $fontResources)
    {
        $this->throwsExceptionIfFontsAreInvalid($fontResources);

        $this->fontResources = $fontResources;
        $this->setStyle(self::STYLE_NORMAL);
    }

    private function throwsExceptionIfFontsAreInvalid(array $fonts)
    {
        $types = array(
            self::STYLE_NORMAL,
            self::STYLE_BOLD,
            self::STYLE_ITALIC,
            self::STYLE_BOLD_ITALIC,
        );

        if(count($fonts) === 0)
        {
            throw new \InvalidArgumentException('Passed empty map of fonts.');
        }
        elseif(count(\array_diff(array_keys($fonts), $types)) > 0)
        {
            throw new \InvalidArgumentException('Invalid font types in map of fonts.');
        }
        elseif(!isset($fonts[self::STYLE_NORMAL]))
        {
            throw new \InvalidArgumentException('Path for normal font must by passed.');
        }
    }

    public function setStyle($style)
    {
        $style = $this->convertStyleType($style);

        $this->currentStyle = $this->fontStyle($style);
    }

    private function convertStyleType($style)
    {
        if(is_string($style))
        {
            $style = str_replace('-', '_', strtoupper($style));
            $const = sprintf('%s::STYLE_%s', __CLASS__, $style);

            if(defined($const))
            {
                $style = constant($const);
            }
            else
            {
                $style = self::STYLE_NORMAL;
            }
        }

        return $style;
    }

    public function hasStyle($style)
    {
        $style = $this->convertStyleType($style);
        return isset($this->fontResources[$style]);
    }

    private function fontStyle($style)
    {
        $font = !$this->hasStyle($style) ? self::STYLE_NORMAL : $style;

        return $font;
    }

    /**
     * @internal Public method within PHPPdf\Core\Engine\ZF namespace
     * 
     * @return Zend\Pdf\Resource\Font
     */
    public function getCurrentWrappedFont()
    {
        return $this->getResourceByStyle($this->currentStyle);
    }

    private function getResourceByStyle($style)
    {
        try
        {
            if(is_string($this->fontResources[$style]))
            {
                $data = $this->fontResources[$style];
                if($this->isNamedFont($data))
                {
                    $name = $this->retrieveFontName($data);
                    $this->fontResources[$style] = ZendFont::fontWithName($name);
                }
                else 
                {
                    $this->fontResources[$style] = ZendFont::fontWithPath($data);
                }
            }
            
            return $this->fontResources[$style];
        }
        catch(\Zend\Pdf\Exception $e)
        {
            InvalidResourceException::invalidFontException($this->fontResources[$style], $e);
        }
    }
    
    private function isNamedFont($fontData)
    {
        return strpos($fontData, '/') === false;
    }
    
    private static function retrieveFontName($name)
    {
        $const = sprintf('Zend\Pdf\Font::FONT_%s', str_replace('-', '_', strtoupper($name)));

        if(!defined($const))
        {
            throw new \InvalidArgumentException(sprintf('Unrecognized font name: "%s".".', $name));
        }

        return constant($const);
    }

    public function getCharsWidth(array $chars, $fontSize)
    {
        $glyphs = $this->getCurrentWrappedFont()->glyphNumbersForCharacters($chars);
        $widths = $this->getCurrentWrappedFont()->widthsForGlyphs($glyphs);
        $textWidth = (array_sum($widths) / $this->getCurrentWrappedFont()->getUnitsPerEm()) * $fontSize;

        return $textWidth;
    }
}