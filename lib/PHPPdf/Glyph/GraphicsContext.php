<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph;

/**
 * Graphics Context
 *
 * Facade for Zend_Pdf_Page class
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class GraphicsContext
{
    const DASHING_PATTERN_SOLID = 0;
    const DASHING_PATTERN_DOTTED = 1;

    private $state = array(
        'fillColor' => null,
        'lineColor' => null,
        'lineWidth' => null,
        'lineDashingPattern' => null,
    );

    private $memento = null;

    /**
     * @var Zend_Pdf_Page
     */
    private $page;

    public function __construct(\Zend_Pdf_Page $page)
    {
        $this->page = $page;
    }

    public function clipRectangle($x1, $y1, $x2, $y2)
    {
        $this->page->clipRectangle($x1, $y1, $x2, $y2);
    }

    public function saveGS()
    {
        $this->page->saveGS();
        $this->memento = $this->state;
    }

    public function restoreGS()
    {
        $this->page->restoreGS();
        $this->state = $this->memento;
        $this->memento = null;
    }

    public function drawImage(\Zend_Pdf_Resource_Image $image, $x1, $y1, $x2, $y2)
    {
        $this->page->drawImage($image, $x1, $y1, $x2, $y2);
    }

    public function drawLine($x1, $y1, $x2, $y2)
    {
        $this->page->drawLine($x1, $y1, $x2, $y2);
    }

    public function setFont(\PHPPdf\Font\Font $font, $size)
    {
        $fontResource = $font->getFont();
        $this->page->setFont($fontResource, $size);
    }

    public function setFillColor($color)
    {
        if(!$this->state['fillColor'] || $color->getComponents() !== $this->state['fillColor']->getComponents())
        {
            $this->page->setFillColor($color);
            $this->state['fillColor'] = $color;
        }
    }

    public function setLineColor($color)
    {
        if(!$this->state['lineColor'] || $color->getComponents() !== $this->state['lineColor']->getComponents())
        {
            $this->page->setLineColor($color);
            $this->state['lineColor'] = $color;
        }
    }

    public function drawPolygon(array $x, array $y, $type)
    {
        $this->page->drawPolygon($x, $y, $type);
    }

    public function drawText($text, $width, $height, $encoding)
    {
        $this->page->drawText($text, $width, $height, $encoding);
    }

    public function __clone()
    {
        $this->page = clone $this->page;
    }

    public function getStyle()
    {
        return $this->page->getStyle();
    }

    public function setStyle($style)
    {
        $this->page->setStyle($style);
    }

    public function getPage()
    {
        return $this->page;
    }

    public function drawRoundedRectangle($x1, $y1, $x2, $y2, $radius, $fillType = \Zend_Pdf_Page::SHAPE_DRAW_FILL_AND_STROKE)
    {
        $this->page->drawRoundedRectangle($x1, $y1, $x2, $y2, $radius, $fillType);
    }

    public function setLineWidth($width)
    {
        if(!$this->state['lineWidth'] || $this->state['lineWidth'] != $width)
        {
            $this->page->setLineWidth($width);
            $this->state['lineWidth'] = $width;
        }
    }

    public function setLineDashingPattern($pattern)
    {
        switch($pattern)
        {
            case self::DASHING_PATTERN_DOTTED:
                $pattern = array(1, 2);
                break;
        }
        
        if($this->state['lineDashingPattern'] === null || $this->state['lineDashingPattern'] !== $pattern)
        {
            $this->page->setLineDashingPattern($pattern);
            $this->state['lineDashingPattern'] = $pattern;
        }
    }
}