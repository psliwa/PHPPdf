<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node;

/**
 * Item of BasicList
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class BasicListItem extends Container
{
    protected function doBreakAt($height)
    {
        $absoluteYCoordOfBreaking = $this->getFirstPoint()->getY() - $height;
        
        if($this->hasLeafDescendants($absoluteYCoordOfBreaking))
        {
            return parent::doBreakAt($height);
        }
        
        return null;
    }
}