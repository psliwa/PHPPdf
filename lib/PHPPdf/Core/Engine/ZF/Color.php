<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Engine\ZF;

use PHPPdf\Exception\InvalidResourceException;
use PHPPdf\Core\Engine\Color as BaseColor;
use Zend\Pdf\Exception;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Color implements BaseColor
{
    /**
     * @var Zend\Pdf\Color
     */
    private $zendColor;
    
    public function __construct($colorData)
    {
        try 
        {
            $this->zendColor = \Zend\Pdf\Color\Html::color($colorData);
        }
        catch(Exception $e)
        {
            InvalidResourceException::invalidColorException($colorData, $e);
        }
    }
    
    public function getComponents()
    {
        return $this->zendColor->getComponents();
    }
    
    /**
     * @return Zend\Pdf\Color
     */
    public function getWrappedColor()
    {
        return $this->zendColor;
    }
}