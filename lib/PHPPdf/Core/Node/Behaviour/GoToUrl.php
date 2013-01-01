<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node\Behaviour;

use PHPPdf\Core\Node\Node,
    PHPPdf\Core\Engine\GraphicsContext;

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
    
    protected function doAttach(GraphicsContext $gc, Node $node)
    {
        $firstPoint = self::getFirstPointOf($node);
        $diagonalPoint = self::getDiagonalPointOf($node);

        $gc->uriAction($firstPoint->getX(), $firstPoint->getY(), $diagonalPoint->getX(), $diagonalPoint->getY(), $this->uri);
    }
}