<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Formatter;

use PHPPdf\Formatter\BaseFormatter,
    PHPPdf\Glyph as Glyphs,
    PHPPdf\Document,
    PHPPdf\Formatter\Chain;

/**
 * Calculates text's real dimension
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class TextDimensionFormatter extends BaseFormatter
{
    private $charCodeMethodName;

    public function __construct($charCodeMethodName = 'ordUtf8')
    {
        $this->setCharCodeMethod($charCodeMethodName);
    }

    private function setCharCodeMethod($methodName)
    {
        if(!is_callable(array($this, $methodName)))
        {
            throw new \InvalidArgumentException('Passed argument is not valid callback.');
        }

        $this->charCodeMethodName = $methodName;
    }

    public function format(Glyphs\Glyph $glyph, Document $document)
    {
        $words = preg_split('/\s+/', $glyph->getText());
        
        $lastIndex = count($words) - 1;
        array_walk($words, function(&$value, $index) use($lastIndex){
            if($index != $lastIndex)
            {
                $value .= ' ';
            }
        });

        $wordsSizes = array();
        
        $font = $glyph->getFont($document);
        $fontSize = $glyph->getFontSizeRecursively();
        
        foreach($words as $word)
        {
            $wordsSizes[] = $this->getTextWidth($font, $fontSize, $word);
        }
        
        $glyph->setWordsSizes($words, $wordsSizes);
    }

    private function getTextWidth($font, $fontSize, $text)
    {
        $callback = array($this, $this->charCodeMethodName);
        if($fontSize)
        {
            $length = strlen($text);
            $chars = array();
            $bytes = 1;
            for($i=0; $i<$length; $i+=$bytes)
            {
                list($char, $bytes) = call_user_func($callback, $text, $i, $bytes);
                if($char !== false)
                {
                    $chars[] = $char;
                }
            }

            $textWidth = $font->getCharsWidth($chars, $fontSize);

            return $textWidth;
        }

        return 0;
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

    public function serialize()
    {
        return serialize($this->charCodeMethodName);
    }

    public function unserialize($serialized)
    {
        $method = unserialize($serialized);

        $this->setCharCodeMethod($method);
    }
}