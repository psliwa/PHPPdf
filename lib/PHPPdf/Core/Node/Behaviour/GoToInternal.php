<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node\Behaviour;

use PHPPdf\Exception\Exception;

use PHPPdf\Core\Node\Node,
    PHPPdf\Core\Engine\GraphicsContext;

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
    
    protected function doAttach(GraphicsContext $gc, Node $node)
    {
        $destinationNode = $this->destination->getNode();
        
        if(!$destinationNode)
        {
            throw new Exception('Destination of GoToInternal dosn\'t exist.');
        }

        $firstPoint = $node->getFirstPoint();
        $diagonalPoint = $node->getDiagonalPoint();
        
        $gc->goToAction($destinationNode->getGraphicsContext(), $firstPoint->getX(), $firstPoint->getY(), $diagonalPoint->getX(), $diagonalPoint->getY(), $destinationNode->getFirstPoint()->getY());
    }
}