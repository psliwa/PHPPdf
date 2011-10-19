<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Engine\ZF;

use PHPPdf\Core\UnitConverter;

use PHPPdf\Exception\Exception;

use PHPPdf\Util;

use PHPPdf\Exception\InvalidResourceException;

use PHPPdf\Core\Engine\GraphicsContext as BaseGraphicsContext,
    PHPPdf\Core\Engine\Engine as BaseEngine;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Engine implements BaseEngine
{
    private static $loadedEngines = array();
    
    private $zendPdf = null;
    private $colors = array();
    private $images = array();
    private $graphicsContexts = array();
    private $outlines = array();
    private $unitConverter;
    
    public function __construct(\Zend_Pdf $zendPdf = null, UnitConverter $unitConverter = null)
    {
        $this->zendPdf = $zendPdf;
        $this->unitConverter = $unitConverter;
    }
    
    public function createGraphicsContext($graphicsContextSize)
    {
        $gc = new GraphicsContext($this, $graphicsContextSize);
        
        return $gc;
    }
    
    public function attachGraphicsContext(BaseGraphicsContext $gc)
    {
        $this->getZendPdf()->pages[] = $gc->getPage();
        $this->graphicsContexts[] = $gc;
    }
    
    public function getAttachedGraphicsContexts()
    {
        return $this->graphicsContexts;
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
            $this->images[$data] = new Image($data, $this->unitConverter);
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
        $this->getZendPdf()->properties['Producer'] = sprintf('PHPPdf %s', \PHPPdf\Version::VERSION);
        
        foreach($this->graphicsContexts as $gc)
        {
            $gc->commit();
        }

        return $this->getZendPdf()->render();
    }
    
    /**
     * @return \Zend_Pdf
     */
    public function getZendPdf()
    {
        if(!$this->zendPdf)
        {
            $this->zendPdf = new \Zend_Pdf();
        }
        
        return $this->zendPdf;
    }
    
    /**
     * @internal
     */
    public function registerOutline($id, \Zend_Pdf_Outline $outline)
    {
        $this->outlines[$id] = $outline;
    }
    
    /**
     * @internal
     */
    public function getOutline($id)
    {
        if(!isset($this->outlines[$id]))
        {
            throw new Exception(sprintf('Bookmark with id "%s" dosn\'t exist.', $id));
        }
        
        return $this->outlines[$id];
    }
    
    public function loadEngine($file)
    {
        if(isset(self::$loadedEngines[$file]))
        {
            return self::$loadedEngines[$file];
        }
        
        if(!is_readable($file))
        {
            InvalidResourceException::fileDosntExistException($file);
        }

        try
        {
            $pdf = \Zend_Pdf::load($file);
            $engine = new self($pdf, $this->unitConverter);
            
            foreach($pdf->pages as $page)
            {
                $gc = new GraphicsContext($engine, $page);
                $engine->attachGraphicsContext($gc);
            }
            
            self::$loadedEngines[$file] = $engine;
            
            return $engine;
        }
        catch(\Zend_Pdf_Exception $e)
        {
            InvalidResourceException::invalidPdfFileException($file, $e);
        }
    }
    
    public function setMetadataValue($name, $value)
    {
        switch($name)
        {
            case 'Trapped':
                $value = $value === 'null' ? null : Util::convertBooleanValue($value);
                $this->getZendPdf()->properties[$name] = $value;
                break;
            case 'CreationDate':
            case 'ModDate':
                $value = \Zend_Pdf::pdfDate(strtotime($value));
                $this->getZendPdf()->properties[$name] = $value;
                break;
            case 'Title':
            case 'Author':
            case 'Subject':
            case 'Keywords':
            case 'Creator':
                $this->getZendPdf()->properties[$name] = $value;
                break;
        }
    }
}