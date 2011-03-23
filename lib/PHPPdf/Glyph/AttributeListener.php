<?php

namespace PHPPdf\Glyph;

/**
 * Listener of attribute's life cycle events
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface AttributeListener
{
    public function attributeChanged(Glyph $glyph, $attributeName, $oldValue);
}