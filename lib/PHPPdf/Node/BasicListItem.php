<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Node;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class BasicListItem extends Container
{
    protected function doSplit($height)
    {
        $absoluteYCoordOfSplit = $this->getFirstPoint()->getY() - $height;
        
        if($this->hasLeafDescendants($absoluteYCoordOfSplit))
        {
            return parent::doSplit($height);
        }
        
        return null;
    }
}