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
class StickyNote extends Behaviour
{
    private $text;
    
    public function __construct($text)
    {
        $this->text = (string) $text;
    }
    
    protected function doAttach(GraphicsContext $gc, Glyph $glyph)
    {
        $firstPoint = $glyph->getFirstPoint();
        $diagonalPoint = $glyph->getDiagonalPoint();

        $gc->attachStickyNote($firstPoint->getX(), $firstPoint->getY(), $diagonalPoint->getX(), $diagonalPoint->getY(), $this->text);
    }
}