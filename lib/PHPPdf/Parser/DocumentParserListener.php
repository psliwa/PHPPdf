<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Parser;

use PHPPdf\Node\PageCollection;
use PHPPdf\Document;
use PHPPdf\Node\Node;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface DocumentParserListener
{
    public function onStartParseNode(Document $document, PageCollection $root, Node $node);
    public function onEndParseNode(Document $document, PageCollection $root, Node $node);
}