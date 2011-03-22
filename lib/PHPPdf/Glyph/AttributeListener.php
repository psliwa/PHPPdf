<?php

namespace PHPPdf\Glyph;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface AttributeListener
{
    public function attributeChanged(Glyph $glyph, $attributeName, $oldValue);
}