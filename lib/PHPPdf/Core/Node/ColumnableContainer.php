<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node;

use PHPPdf\Exception\InvalidArgumentException;
use PHPPdf\Util;
use PHPPdf\Core\Document;

/**
 * Container being able to format as columns
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ColumnableContainer extends Container
{
    protected static function setDefaultAttributes()
    {
        parent::setDefaultAttributes();
        
        static::addAttribute('number-of-columns', 2);
        static::addAttribute('margin-between-columns', 10);
        static::addAttribute('equals-columns', false);
    }

    public function setNumberOfColumns($count)
    {
        $count = (int) $count;

        if($count < 2)
        {
            throw new InvalidArgumentException(sprintf('Number of columns should be integer greater than 1, %d given.', $count));
        }

        $this->setAttributeDirectly('number-of-columns', $count);

        return $this;
    }
    
    protected function beforeFormat(Document $document)
    {
        $parent = $this->getParent();

        $width = ($parent->getWidth() - ($this->getAttribute('number-of-columns')-1)*$this->getAttribute('margin-between-columns')) / $this->getAttribute('number-of-columns');
        $this->setWidth($width);
    }
    
    protected static function initializeType()
    {
        parent::initializeType();
        
        static::setAttributeSetters(array('equals-columns' => 'setEqualsColumns'));
    }

    public function setEqualsColumns($flag)
    {
        $flag = Util::convertBooleanValue($flag);
        
        $this->setAttributeDirectly('equals-columns', $flag);
    }
}