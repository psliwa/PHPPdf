<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Formatter;

use PHPPdf\Core\Node\Node,
    PHPPdf\Core\Document;

/**
 * Node formatter
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface Formatter
{
    public function format(Node $node, Document $document);
}