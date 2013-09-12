<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node;

use PHPPdf\Core\ComplexAttribute\Background;
use PHPPdf\Util;
use PHPPdf\Exception\InvalidArgumentException;
use PHPPdf\Core\DrawingTask;
use PHPPdf\Core\Document;
use PHPPdf\Core\DrawingTaskHeap;

/**
 * Barcode node class
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Barcode extends Node
{
    const TYPE_CODE128 = 'code128';
    const TYPE_CODE25 = 'code25';
    const TYPE_CODE25INTERLEAVED = 'code25interleaved';
    const TYPE_CODE39 = 'code39';
    const TYPE_EAN13 = 'ean13';
    const TYPE_EAN2 = 'ean2';
    const TYPE_EAN5 = 'ean5';
    const TYPE_EAN8 = 'ean8';
    const TYPE_IDENTCODE = 'identcode';
    const TYPE_ITF14 = 'itf14';
    const TYPE_LEITCODE = 'leitcode';
    const TYPE_PLANET = 'planet';
    const TYPE_POSTNET = 'postnet';
    const TYPE_ROYALMAIL = 'royalmail';
    const TYPE_UPCA = 'upca';
    const TYPE_UPCE = 'upce';
    
    private $barcode = null;
    
    protected static function setDefaultAttributes()
    {
        parent::setDefaultAttributes();
        
        static::addAttribute('type', self::TYPE_CODE128);
        static::addAttribute('code');
        static::addAttribute('draw-code', true);
        static::addAttribute('bar-height', 50);
        static::addAttribute('with-checksum', false);
        static::addAttribute('with-checksum-in-text', false);
        static::addAttribute('bar-thin-width', 1);
        static::addAttribute('bar-thick-width', 3);
        static::addAttribute('factor', 1);
    }
    
    protected static function initializeType()
    {
        static::setAttributeSetters(array('type' => 'setType'));
        static::setAttributeSetters(array('draw-code' => 'setDrawCode'));
        static::setAttributeSetters(array('bar-height' => 'setBarHeight'));
        static::setAttributeSetters(array('with-checksum' => 'setWithChecksum'));
        static::setAttributeSetters(array('with-checksum-in-text' => 'setWithChecksumInText'));
        static::setAttributeSetters(array('bar-thin-width' => 'setBarThinWidth'));
        static::setAttributeSetters(array('bar-thick-width' => 'setBarThickWidth'));
        
        parent::initializeType();
    }
    
    public function setType($type)
    {
        $const = sprintf('%s::TYPE_%s', __CLASS__, strtoupper($type));
        
        if(!defined($const))
        {
            throw new InvalidArgumentException(sprintf('Barcode type "%s" dosn\'t exist.', $type));
        }
        
        $type = constant($const);
        
        $this->setAttributeDirectly('type', $type);
    }
    
    public function setDrawCode($flag)
    {
        $flag = $this->filterBooleanValue($flag);
        $this->setAttributeDirectly('draw-code', $flag);
    }
    
    public function setWithChecksum($flag)
    {
        $flag = $this->filterBooleanValue($flag);
        $this->setAttributeDirectly('with-checksum', $flag);
    }

    public function setWithChecksumInText($flag)
    {
        $flag = $this->filterBooleanValue($flag);
        $this->setAttributeDirectly('with-checksum-in-text', $flag);
    }
    
    public function setBarHeight($height)
    {
        $height = $this->convertUnit($height);
        $this->setAttributeDirectly('bar-height', $height);
    }
    
    public function setBarThinWidth($value)
    {
        $value = $this->convertUnit($value);
        $this->setAttributeDirectly('bar-thin-width', $value);
    }

    public function setBarThickWidth($value)
    {
        $value = $this->convertUnit($value);
        $this->setAttributeDirectly('bar-thick-width', $value);
    }
    
    public function setAttribute($name, $value)
    {
        $this->barcode = null;

        parent::setAttribute($name, $value);
    }
    
    protected function doDraw(Document $document, DrawingTaskHeap $tasks)
    {
        $callback = function(Barcode $node, Document $document){
            $barcode = $node->getBarcode($document);
            $gc = $node->getGraphicsContext();
            $gc->drawBarcode($node->getFirstPoint()->getX(), $node->getFirstPoint()->getY(), $barcode);
        };
        
        $tasks->insert(new DrawingTask($callback, array($this, $document)));
    }
    
    protected function getDrawingTasksFromComplexAttributes(Document $document, DrawingTaskHeap $tasks)
    {
        $complexAttributes = $document->getComplexAttributes($this->complexAttributeBag);
        foreach($complexAttributes as $complexAttribute)
        {
            if(!$complexAttribute instanceof Background)
            {
                $this->insertComplexAttributeTask($complexAttribute, $tasks, $document);
            }
        }
    }
    
    /**
     * @internal
     */
    public function getBarcode(Document $document)
    {
        if($this->barcode === null)
        {
            $this->barcode = $this->createBarcode($document);
        }
        
        return $this->barcode;
    }
    
    private function createBarcode(Document $document)
    {
        try
        {
            $foreColor = $this->convertBarcodeColor($document, $this->getRecurseAttribute('color'));
            $background = $this->getComplexAttributes('background');
            $backgroundColor = isset($background['color']) ? $this->convertBarcodeColor($document, $background['color']) : '#FFFFFF';

            $barcodeClass = sprintf('Zend\Barcode\Object\%s', ucfirst($this->getAttribute('type')));

            $barcode = new $barcodeClass(array(
                'text' => $this->getAttribute('code'),
                'font' => $this->getFont($document)->getCurrentResourceIdentifier(),
                'fontSize' => $this->getFontSizeRecursively(),
                'foreColor' => $foreColor,
                'drawText' => $this->getAttribute('draw-code'),
                'barHeight' => $this->getAttribute('bar-height'),
                'withChecksum' => $this->getAttribute('with-checksum'),
                'withChecksumInText' => $this->getAttribute('with-checksum-in-text'),
                'orientation' => $this->getOrientation(),
                'barThinWidth' => $this->getAttribute('bar-thin-width'),
                'barThickWidth' => $this->getAttribute('bar-thick-width'),
                'factor' => (float) $this->getAttribute('factor'),
                'backgroundColor' => $backgroundColor,
            ));
            
            return $barcode;
        }
        catch(\Zend\Barcode\Exception $e)
        {
            throw new InvalidArgumentException('Invalid arguments passed to barcode, see cause exception for more details.', $previous->getCode(), $e);
        }
    }
    
    private function convertBarcodeColor(Document $document, $color)
    {
        return strtoupper($document->getColorFromPalette($color));;
    }
    
    private function getOrientation()
    {
        $radians = (float) Util::convertAngleValue($this->getAttributeDirectly('rotate'));
        
        return rad2deg($radians);
    }

    protected function beforeFormat(Document $document)
    {
        $barcode = $this->getBarcode($document);
        $this->setHeight($barcode->getHeight(true)/2);
        $this->setWidth($barcode->getWidth(true)/2);
    }
    
    public function getRotate()
    {
        return null;
    }
}