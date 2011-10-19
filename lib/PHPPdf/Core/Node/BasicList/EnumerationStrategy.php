<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node\BasicList;


use PHPPdf\Core\Document;
use PHPPdf\Core\Engine\GraphicsContext,
    PHPPdf\Core\Node\BasicList;

/**
 * Strategy of liste enumeration
 * 
 * Object of this class is able to draw sequence of enumeration
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface EnumerationStrategy
{
    /**
     * Draw enumeration
     */
    public function drawEnumeration(Document $document, BasicList $list, GraphicsContext $gc);
    
    public function reset();
    
    /**
     * @return double Width of the widest enumeration element (text, image etc.)
     */
    public function getWidthOfTheBiggestPosibleEnumerationElement(Document $document, BasicList $list);
    public function setIndex($index);
    public function setVisualIndex($visualIndex);
    public function incrementIndex();
}