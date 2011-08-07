<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Engine\ZF;

use PHPPdf\Exception\InvalidResourceException,
    PHPPdf\Engine\Color as BaseColor;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Color implements BaseColor
{
    /**
     * @var Zend_Pdf_Color
     */
    private $zendColor;
    
    public function __construct($colorData)
    {
        try 
        {
            $this->zendColor = \Zend_Pdf_Color_Html::color($colorData);
        }
        catch(\Zend_Pdf_Exception $e)
        {
            InvalidResourceException::invalidColorException($colorData, $e);
        }
    }
    
    public function getComponents()
    {
        return $this->zendColor->getComponents();
    }
    
    /**
     * @return Zend_Pdf_Color
     */
    public function getWrappedColor()
    {
        return $this->zendColor;
    }
}