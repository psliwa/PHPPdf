<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Formatter;

use PHPPdf\Formatter\Formatter,
    PHPPdf\Node as Nodes,
    PHPPdf\Formatter\Chain;

/**
 * Sets chain to children nodes
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ContainerFormatter extends BaseFormatter
{
    public function format(Nodes\Node $node, \PHPPdf\Document $document)
    {
        foreach($node->getChildren() as $child)
        {
            $child->format($document);
        }
    }
}