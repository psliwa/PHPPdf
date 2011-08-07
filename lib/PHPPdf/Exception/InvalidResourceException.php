<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Exception;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class InvalidResourceException extends Exception
{
    public static function invalidColorException($color, \Exception $previous = null)
    {
        throw new self(sprintf('Color "%s" not found.', $color), 0, $previous);
    }
    
    public static function invalidImageException($imagePath, \Exception $previous = null)
    {
        throw new self(sprintf('Image "%s" can\'t be initialized.', $imagePath), 0, $previous);
    }
    
    public static function invalidFontException($fontData, \Exception $previous = null)
    {
        throw new self(sprintf('Font "%s" not found.', $fontData), 0, $previous);
    }
}