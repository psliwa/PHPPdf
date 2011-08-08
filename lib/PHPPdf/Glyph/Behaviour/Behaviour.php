<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph\Behaviour;

use PHPPdf\Glyph\Glyph,
    PHPPdf\Engine\GraphicsContext;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface Behaviour
{
    public function attach(GraphicsContext $gc, Glyph $glyph);
}