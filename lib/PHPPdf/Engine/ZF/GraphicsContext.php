<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Engine\ZF;

use PHPPdf\Engine\GraphicsContext as BaseGraphicsContext,
    PHPPdf\Engine\Color as BaseColor,
    PHPPdf\Engine\Font as BaseFont,
    PHPPdf\Engine\Image as BaseImage;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class GraphicsContext implements BaseGraphicsContext
{
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

    public function drawImage(BaseImage $image, $x1, $y1, $x2, $y2)
    {
        $this->page->drawImage($image->getWrappedImage(), $x1, $y1, $x2, $y2);
    }

    public function drawLine($x1, $y1, $x2, $y2)
    {
        $this->page->drawLine($x1, $y1, $x2, $y2);
    }

    public function setFont(BaseFont $font, $size)
    {
        $fontResource = $font->getCurrentWrappedFont();
        $this->page->setFont($fontResource, $size);
    }

    public function setFillColor(BaseColor $color)
    {
        if(!$this->state['fillColor'] || $color->getComponents() !== $this->state['fillColor']->getComponents())
        {
            $this->page->setFillColor($color->getWrappedColor());
            $this->state['fillColor'] = $color;
        }
    }

    public function setLineColor(BaseColor $color)
    {
        if(!$this->state['lineColor'] || $color->getComponents() !== $this->state['lineColor']->getComponents())
        {
            $this->page->setLineColor($color->getWrappedColor());
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

    public function getPage()
    {
        return $this->page;
    }

    public function drawRoundedRectangle($x1, $y1, $x2, $y2, $radius, $fillType = self::SHAPE_DRAW_FILL_AND_STROKE)
    {
        $this->page->drawRoundedRectangle($x1, $y1, $x2, $y2, $radius, $this->translateFillType($fillType));
    }
    
    private function translateFillType($fillType)
    {
        switch($fillType)
        {
            case self::SHAPE_DRAW_STROKE:
                return \Zend_Pdf_Page::SHAPE_DRAW_STROKE;
            case self::SHAPE_DRAW_FILL:
                return \Zend_Pdf_Page::SHAPE_DRAW_FILL;
            case self::SHAPE_DRAW_FILL_AND_STROKE:
                return \Zend_Pdf_Page::SHAPE_DRAW_FILL_AND_STROKE;
            default:
                throw new \InvalidArgumentException(sprintf('Invalid filling type "%s".', $fillType));
        }
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
    
    public function uriAction($x1, $y1, $x2, $y2, $uri)
    {
        $uriAction = \Zend_Pdf_Action_URI::create($uri);
        
        $annotation = \Zend_Pdf_Annotation_Link::create($x1, $y1, $x2, $y2, $uriAction);
        
        $this->page->attachAnnotation($annotation);
    }
    
    public function goToAction(BaseGraphicsContext $gc, $x1, $y1, $x2, $y2, $top)
    {
        $destination = \Zend_Pdf_Destination_FitHorizontally::create($gc->getPage(), $top);   
        
        $annotation = \Zend_Pdf_Annotation_Link::create($x1, $y1, $x2, $y2, $destination);
        
        $this->page->attachAnnotation($annotation);
    }
}