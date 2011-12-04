<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Engine\ZF;

use PHPPdf\Exception\InvalidResourceException;
use PHPPdf\Core\Engine\AbstractFont;
use Zend\Pdf\Font as ZendFont;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Font extends AbstractFont
{
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

    public function getWidthOfText($text, $fontSize)
    {
        $chars = $this->convertTextToChars($text);
        
        $glyphs = $this->getCurrentWrappedFont()->glyphNumbersForCharacters($chars);
        $widths = $this->getCurrentWrappedFont()->widthsForGlyphs($glyphs);
        $textWidth = (array_sum($widths) / $this->getCurrentWrappedFont()->getUnitsPerEm()) * $fontSize;

        return $textWidth;
    }
    
    private function convertTextToChars($text)
    {
        $length = strlen($text);
        $chars = array();
        $bytes = 1;
        for($i=0; $i<$length; $i+=$bytes)
        {
            list($char, $bytes) = $this->ordUtf8($text, $i, $bytes);
            if($char !== false)
            {
                $chars[] = $char;
            }
        }
        
        return $chars;
    }
    
    /**
     * code from http://php.net/manual/en/function.ord.php#78032
     */
    private function ordUtf8($text, $index = 0, $bytes = null)
    {
        $len = strlen($text);
        $bytes = 0;

        $char = false;

        if ($index < $len)
        {
            $h = ord($text{$index});

            if($h <= 0x7F)
            {
                $bytes = 1;
                $char = $h;
            }
            elseif ($h < 0xC2)
            {
                $char = false;
            }
            elseif ($h <= 0xDF && $index < $len - 1)
            {
                $bytes = 2;
                $char = ($h & 0x1F) <<  6 | (ord($text{$index + 1}) & 0x3F);
            }
            elseif($h <= 0xEF && $index < $len - 2)
            {
                $bytes = 3;
                $char = ($h & 0x0F) << 12 | (ord($text{$index + 1}) & 0x3F) << 6
                                         | (ord($text{$index + 2}) & 0x3F);
            }
            elseif($h <= 0xF4 && $index < $len - 3)
            {
                $bytes = 4;
                $char = ($h & 0x0F) << 18 | (ord($text{$index + 1}) & 0x3F) << 12
                                         | (ord($text{$index + 2}) & 0x3F) << 6
                                         | (ord($text{$index + 3}) & 0x3F);
            }
        }


        return array($char, $bytes);
    }
}