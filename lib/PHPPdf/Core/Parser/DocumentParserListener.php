<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Parser;

use PHPPdf\Core\Node\PageCollection;
use PHPPdf\Core\Document;
use PHPPdf\Core\Node\Node;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface DocumentParserListener
{
    public function onStartParseNode(Document $document, PageCollection $root, Node $node);
    public function onEndParseNode(Document $document, PageCollection $root, Node $node);
    public function onEndParsePlaceholders(Document $document, PageCollection $root, Node $node);
    public function onEndParsing(Document $document, PageCollection $root);
}