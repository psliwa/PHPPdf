<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Formatter;

use PHPPdf\Core\Node as Nodes,
    PHPPdf\Core\Document,
    PHPPdf\Core\Point;

/**
 * Calculates text's real dimension
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class TextResetPositionFormatter extends BaseFormatter
{
    public function format(Nodes\Node $node, Document $document)
    {
        $boundary = $node->getBoundary();
        list($x, $y) = $node->getFirstPoint()->toArray();
        $boundary->reset();

        $boundary->setNext($x, $y);
    }
}