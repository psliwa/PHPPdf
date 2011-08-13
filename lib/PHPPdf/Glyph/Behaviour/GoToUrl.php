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
class GoToUrl extends Behaviour
{
    private $uri;
    
    public function __construct($uri)
    {
        $this->uri = (string) $uri;
    }
    
    protected function doAttach(GraphicsContext $gc, Glyph $glyph)
    {
        $firstPoint = $glyph->getFirstPoint();
        $diagonalPoint = $glyph->getDiagonalPoint();

        $gc->uriAction($firstPoint->getX(), $firstPoint->getY(), $diagonalPoint->getX(), $diagonalPoint->getY(), $this->uri);
    }
}