<?php

namespace PHPPdf\Glyph\BasicList;

use PHPPdf\Glyph\GraphicsContext,
    PHPPdf\Glyph\BasicList;

abstract class AbstractEnumerationStrategy implements EnumerationStrategy
{
    private $initialIndex = 1;
    
	public function getInitialIndex()
    {
        return $this->initialIndex;
    }

	public function setInitialIndex($index)
    {
        $this->initialIndex = $index;
    }
    
    public function drawEnumeration(BasicList $list, GraphicsContext $gc, $elementIndex)
    {
        $child = $list->getChild($elementIndex);
        
        $point = $child->getFirstPoint();        
        
        list($xTranslation, $yTranslation) = $this->getEnumerationElementTranslations($list, $elementIndex);
        
        $xCoord = $point->getX() - $child->getMarginLeft() + $xTranslation;
        $yCoord = $point->getY() - $yTranslation;
        
        $this->doDrawEnumeration($list, $gc, $xCoord, $yCoord);
    }
    
    abstract protected function getEnumerationElementTranslations(BasicList $list, $elementIndex);
    
    abstract protected function doDrawEnumeration(BasicList $list, GraphicsContext $gc, $xCoord, $yCoord);
    
    public function reset()
    {
    }
}