<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph;

use PHPPdf\Util;

use PHPPdf\Document;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ColumnableContainer extends Container
{
    public function initialize()
    {
        parent::initialize();

        $this->addAttribute('number-of-columns', 2);
        $this->addAttribute('margin-between-columns', 10);
        $this->addAttribute('equals-columns', false);
    }

    public function setNumberOfColumns($count)
    {
        $count = (int) $count;

        if($count < 2)
        {
            throw new \InvalidArgumentException(sprintf('Number of columns should be integer greater than 1, %d given.', $count));
        }

        $this->setAttributeDirectly('number-of-columns', $count);

        return $this;
    }
    
    public function preFormat(Document $document)
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