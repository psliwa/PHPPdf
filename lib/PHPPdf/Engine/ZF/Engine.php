<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Engine\ZF;

use PHPPdf\Engine\GraphicsContext as BaseGraphicsContext,
    PHPPdf\Engine\Engine as BaseEngine;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Engine implements BaseEngine
{
    private $zendPdf = null;
    private $colors = array();
    private $images = array();
    
    public function __construct(\Zend_Pdf $zendPdf = null)
    {
        $this->zendPdf = $zendPdf ? : new \Zend_Pdf();
    }
    
    public function createGraphicsContext($graphicsContextSize)
    {
        $page = new \Zend_Pdf_Page($graphicsContextSize);
        
        $gc = new GraphicsContext($this, $page);
        
        return $gc;
    }
    
    public function attachGraphicsContext(BaseGraphicsContext $gc)
    {
        $this->zendPdf->pages[] = $gc->getPage();
    }
    
    /**
     * @return Color
     */
    public function createColor($data)
    {
        $data = (string) $data;

        if(!isset($this->colors[$data]))
        {
            $this->colors[$data] = new Color($data);
        }
        
        return $this->colors[$data];
    }
    
    /**
     * @return Image
     */
    public function createImage($data)
    {
        $data = (string) $data;

        if(!isset($this->images[$data]))
        {
            $this->images[$data] = new Image($data);
        }
        
        return $this->images[$data];
    }
    
    /**
     * @return Font
     */
    public function createFont($fontData)
    {
        return new Font($fontData);
    }
    
    public function render()
    {
        return $this->zendPdf->render();
    }
    
    /**
     * @return \Zend_Pdf
     */
    public function getZendPdf()
    {
        return $this->zendPdf;
    }
}