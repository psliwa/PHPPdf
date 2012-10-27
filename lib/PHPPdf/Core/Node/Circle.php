<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node;

class Circle extends Container
{
    protected static function setDefaultAttributes()
    {
        parent::setDefaultAttributes();
        
        static::addAttribute('radius');
    }
    
    public function setRadius($value)
    {
        $radius = $this->convertUnit($value);
        $this->setAttributeDirectly('radius', $this->convertUnit($value));

        $size = 2*$radius;
        $this->setWidth($size);
        $this->setHeight($size);
    }
    
    protected static function initializeType()
    {
        parent::initializeType();
        static::setAttributeSetters(array('radius' => 'setRadius'));
    }

    public function getShape()
    {
        return self::SHAPE_ELLIPSE;
    }
}