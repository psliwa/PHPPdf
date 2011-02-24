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

    public function __construct($color = null)
    {
        if($color !== null && !$color instanceof Zend_Pdf_Color)
        {
            $color = \Zend_Pdf_Color_Html::color($color);
        }

        $this->color = $color;
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
}