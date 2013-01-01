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
class StickyNote extends Behaviour
{
    private $text;
    
    public function __construct($text)
    {        
        $this->text = (string) $text;
    }
    
    protected function doAttach(GraphicsContext $gc, Node $node)
    {
        $firstPoint = self::getFirstPointOf($node);
        $diagonalPoint = self::getDiagonalPointOf($node);

        $gc->attachStickyNote($firstPoint->getX(), $firstPoint->getY(), $diagonalPoint->getX(), $diagonalPoint->getY(), $this->text);
    }
}