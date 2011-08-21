<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph\BasicList;

use PHPPdf\Document;

use PHPPdf\Engine\GraphicsContext,
    PHPPdf\Glyph\BasicList;

abstract class AbstractEnumerationStrategy implements EnumerationStrategy
{
    protected $index = 0;
    protected $visualIndex = 1;
    
	public function getInitialIndex()
    {
        return $this->index;
    }

	public function setIndex($index, $visualIndex = null)
    {
        $this->index = $index;
    }
    
    public function setVisualIndex($visualIndex)
    {
        $this->visualIndex = $visualIndex;
    }
    
    public function drawEnumeration(Document $document, BasicList $list, GraphicsContext $gc)
    {
        $child = $list->getChild($this->index);
        
        $point = $child->getFirstPoint();        
        
        list($xTranslation, $yTranslation) = $this->getEnumerationElementTranslations($document, $list);
        
        $xCoord = $point->getX() - $child->getMarginLeft() + $xTranslation;
        $yCoord = $point->getY() - $yTranslation;
        
        $this->doDrawEnumeration($document, $list, $gc, $xCoord, $yCoord);
        
        $this->index++;
        $this->visualIndex++;
    }
    
    abstract protected function getEnumerationElementTranslations(Document $document, BasicList $list);
    
    abstract protected function doDrawEnumeration(Document $document, BasicList $list, GraphicsContext $gc, $xCoord, $yCoord);
    
    public function reset()
    {
    }
}