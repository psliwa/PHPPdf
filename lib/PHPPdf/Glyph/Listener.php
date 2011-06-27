<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph;

/**
 * Listener of attribute's life cycle events
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface Listener
{
    public function attributeChanged(Glyph $glyph, $attributeName, $oldValue);
    public function parentBind(Glyph $glyph);
}