<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph\BasicList;

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
    
    public function drawEnumeration(BasicList $list, GraphicsContext $gc)
    {
        $child = $list->getChild($this->index);
        
        $point = $child->getFirstPoint();        
        
        list($xTranslation, $yTranslation) = $this->getEnumerationElementTranslations($list);
        
        $xCoord = $point->getX() - $child->getMarginLeft() + $xTranslation;
        $yCoord = $point->getY() - $yTranslation;
        
        $this->doDrawEnumeration($list, $gc, $xCoord, $yCoord);
        
        $this->index++;
        $this->visualIndex++;
    }
    
    abstract protected function getEnumerationElementTranslations(BasicList $list);
    
    abstract protected function doDrawEnumeration(BasicList $list, GraphicsContext $gc, $xCoord, $yCoord);
    
    public function reset()
    {
    }
}