<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Node\BasicList;


use PHPPdf\Document;

use PHPPdf\Engine\GraphicsContext,
    PHPPdf\Node\BasicList;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface EnumerationStrategy
{
    public function drawEnumeration(Document $document, BasicList $list, GraphicsContext $gc);
    public function reset();
    public function getWidthOfTheBiggestPosibleEnumerationElement(Document $document, BasicList $list);
    public function setIndex($index);
    public function setVisualIndex($visualIndex);
    public function incrementIndex();
}