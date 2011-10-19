<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node;

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