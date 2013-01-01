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
 * Base class for enumeration strategy
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
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
        $positionTranslation = $list->getPositionTranslation();

        list($xTranslation, $yTranslation) = $this->getEnumerationElementTranslations($document, $list);
        
        $point = $point->translate($positionTranslation->getX(), $positionTranslation->getY());
        $xCoord = $point->getX() - $child->getMarginLeft() + $xTranslation;
        $subchild = current($child->getChildren());
        $yCoord = $point->getY() - $yTranslation - ($subchild ? $subchild->getPaddingTop() : 0) - $child->getPaddingTop();

        $this->doDrawEnumeration($document, $list, $gc, $xCoord, $yCoord);
        
        $this->incrementIndex();
        $this->visualIndex++;
    }
    
    public function incrementIndex()
    {
        $this->index++;
    }
    
    abstract protected function getEnumerationElementTranslations(Document $document, BasicList $list);
    
    abstract protected function doDrawEnumeration(Document $document, BasicList $list, GraphicsContext $gc, $xCoord, $yCoord);
    
    public function reset()
    {
    }
}