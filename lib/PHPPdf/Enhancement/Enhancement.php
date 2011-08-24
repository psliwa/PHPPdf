<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Enhancement;

use PHPPdf\Glyph\Glyph,
    PHPPdf\Glyph\Page,
    PHPPdf\Engine\GraphicsContext,
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

    public function enhance(Glyph $glyph, Document $document)
    {
        $color = $this->getColor();
        $alpha = $glyph->getAlpha();
        
        $graphicsContext = $glyph->getGraphicsContext();
        
        $isAlphaSet = $alpha != 1 && $alpha !== null;
        
        if($color || $isAlphaSet)
        {
            $graphicsContext->saveGS();
        }
        
        if($alpha !== null)
        {
            $graphicsContext->setAlpha($alpha);
        }

        if($color)
        {
            $graphicsContext->setLineColor($color);
            $graphicsContext->setFillColor($color);
        }

        $this->doEnhance($graphicsContext, $glyph, $document);
        
        if($color || $isAlphaSet)
        {
            $graphicsContext->restoreGs();
        }
    }

    abstract protected function doEnhance($gc, Glyph $glyph, Document $document);

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
    
    protected function getConstantValue($majorName, $miniorName)
    {
        $const = sprintf('%s::%s_%s', get_class($this), $majorName, strtoupper($miniorName));

        if(!defined($const))
        {
            throw new \InvalidArgumentException(sprintf('Invalid value for "%s" property, "%s" given.', strtolower($majorName), $miniorName));
        }

        return constant($const);
    }
}