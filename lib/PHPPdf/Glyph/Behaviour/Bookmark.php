<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph\Behaviour;

use PHPPdf\Engine\GraphicsContext,
    PHPPdf\Glyph\Glyph;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Bookmark extends Behaviour
{
    private $name;
    
    public function __construct($name)
    {
        $this->name = (string) $name;
    }

    protected function doAttach(GraphicsContext $gc, Glyph $glyph)
    {
        $gc->addBookmark($this->name, $glyph->getFirstPoint()->getY());
        $this->setPassive(true);
    }
}