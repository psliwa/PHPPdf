<?php

namespace PHPPdf\Glyph;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class TextTransformator
{
    public function __construct(array $replacements = array())
    {
        $this->setReplacements($replacements);
    }
    
    public function setReplacements(array $replacements)
    {
        $this->replacements = $replacements;
    }
    
    public function transform($text)
    {
        return strtr($text, $this->replacements);
    }
}