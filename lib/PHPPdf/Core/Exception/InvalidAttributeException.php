<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Exception;

use PHPPdf\Exception\InvalidArgumentException;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class InvalidAttributeException extends InvalidArgumentException
{
    private $attributeName;
    
    public function __construct($attributeName)
    {
        $this->attributeName = $attributeName;
        
        parent::__construct(sprintf('Attribute "%s" do not exist.', $attributeName));
    }
    
    public function getAttributeName()
    {
        return $this->attributeName;
    }
}