<?php

namespace PHPPdf\Glyph\BasicList;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface EnumerationStrategy
{
    public function getWidthOfCurrentEnumerationChars();
    public function getWidthOfLastEnumerationChars();
    public function getCurrentEnumerationText();
    public function next();
}