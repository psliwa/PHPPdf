<?php

namespace PHPPdf\Enhancement;

use PHPPdf\Glyph\Glyph,
    PHPPdf\Glyph\Page,
    PHPPdf\Glyph\GraphicsContext,
    PHPPdf\Document;

/**
 * Base class of enhancement glyph's visual representation.
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
abstract class Enhancement
{
    private $color;
    private $radius;

    public function __construct($color = null, $radius = null)
    {
        if($color !== null && !$color instanceof \Zend_Pdf_Color)
        {
            $color = \Zend_Pdf_Color_Html::color($color);
        }

        $this->color = $color;
        $this->setRadius($radius);
    }

    private function setRadius($radius)
    {
        if(is_string($radius) && \strpos($radius, ' ') !== false)
        {
            $radius = explode(' ', $radius);
            $count = count($radius);

            while($count < 4)
            {
                $radius[] = current($radius);
                $count++;
            }
        }

        $this->radius = $radius;
    }

    public function getRadius()
    {
        return $this->radius;
    }

    public function getPriority()
    {
        return Document::DRAWING_PRIORITY_BACKGROUND2;
    }

    public function getColor()
    {
        return $this->color;
    }

    public function enhance(Page $page, Glyph $glyph)
    {
        $color = $this->getColor();
        $graphicsContext = $page->getGraphicsContext();

        if($color)
        {
            $graphicsContext->saveGS();
            $graphicsContext->setLineColor($color);
            $graphicsContext->setFillColor($color);
        }

        $this->doEnhance($page, $glyph);
        
        if($color)
        {
            $graphicsContext->restoreGs();
        }
    }

    abstract protected function doEnhance(Page $page, Glyph $glyph);

    protected function drawBoundary(GraphicsContext $gc, $points, $drawType, $shift = 0.5)
    {
        $x = array();
        $y = array();

        foreach($points as $point)
        {
            $x[] = $point[0];
            $y[] = $point[1];
        }

        $x[0] = $x[0] - $shift;
        $index = count($y)-1;
        $y[$index] = $y[$index]+$shift;

        $gc->drawPolygon($x, $y, $drawType);
    }

    protected function drawRoundedBoundary(GraphicsContext $gc, $x1, $y1, $x2, $y2, $fillType)
    {
        $gc->drawRoundedRectangle($x1, $y1, $x2, $y2, $this->getRadius(), $fillType);
    }
}