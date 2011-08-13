<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph\Behaviour;

use PHPPdf\Exception\Exception;

use PHPPdf\Glyph\Glyph,
    PHPPdf\Engine\GraphicsContext;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class GoToInternal extends Behaviour
{
    private $destination;
    
    public function __construct($destination)
    {
        $this->destination = $destination;
    }
    
    protected function doAttach(GraphicsContext $gc, Glyph $glyph)
    {
        $destinationGlyph = $this->destination->getGlyph();
        
        if(!$destinationGlyph)
        {
            throw new Exception('Destination of GoToInternal dosn\'t exist.');
        }

        $firstPoint = $glyph->getFirstPoint();
        $diagonalPoint = $glyph->getDiagonalPoint();
        
        $gc->goToAction($destinationGlyph->getGraphicsContext(), $firstPoint->getX(), $firstPoint->getY(), $diagonalPoint->getX(), $diagonalPoint->getY(), $destinationGlyph->getFirstPoint()->getY());
    }
}