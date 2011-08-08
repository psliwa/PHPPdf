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
class GoToUrl implements Behaviour
{
    private $uri;
    
    public function __construct($uri)
    {
        $this->uri = (string) $uri;
    }
    
    public function attach(GraphicsContext $gc, Glyph $glyph)
    {
        $firstPoint = $glyph->getFirstPoint();
        $diagonalPoint = $glyph->getDiagonalPoint();

        $gc->uriAction($firstPoint->getX(), $firstPoint->getY(), $diagonalPoint->getX(), $diagonalPoint->getY(), $this->uri);
    }
}