<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\ComplexAttribute;

use PHPPdf\Core\Boundary;

use PHPPdf\Exception\InvalidArgumentException;

use PHPPdf\Core\Node\Node,
    PHPPdf\Core\Node\Page,
    PHPPdf\Core\Engine\GraphicsContext,
    PHPPdf\Core\Document;

/**
 * Base class of complex attribute
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
abstract class ComplexAttribute
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

    public function enhance(Node $node, Document $document)
    {
        $color = $document->getColorFromPalette($this->getColor());
        $alpha = $node->getAlpha();
        
        $graphicsContext = $node->getGraphicsContext();
        
        $isAlphaSet = $alpha != 1 && $alpha !== null;
        
        $rotationNode = $node->getAncestorWithRotation();
        
        if($color || $isAlphaSet || $rotationNode)
        {
            $graphicsContext->saveGS();
        }
        
        if($rotationNode)
        {
            $middlePoint = $rotationNode->getMiddlePoint();
            $graphicsContext->rotate($middlePoint->getX(), $middlePoint->getY(), $rotationNode->getRotate());
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

        $this->doEnhance($graphicsContext, $node, $document);
        
        if($color || $isAlphaSet || $rotationNode)
        {
            $graphicsContext->restoreGs();
        }
    }

    abstract protected function doEnhance($gc, Node $node, Document $document);

    protected function drawBoundary(GraphicsContext $gc, $points, $drawType, $shift = 0)
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
    
    protected function drawCircle(GraphicsContext $gc, $radius, $x, $y, $drawType)
    {
        $size = $radius*2;
        $gc->drawEllipse($x, $y, $size, $size, $drawType);
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
            throw new InvalidArgumentException(sprintf('Invalid value for "%s" property, "%s" given.', strtolower($majorName), $miniorName));
        }

        return constant($const);
    }
    
    public function isEmpty()
    {
        return false;
    }
    
    protected function getTranslationAwareBoundary(Node $node, Boundary $boundary)
    {
        $positionTranslation = $node->getPositionTranslation();

        if($positionTranslation && ($positionTranslation->getX() != 0 || $positionTranslation->getY() != 0))
        {
            $boundary = clone $boundary;
            $boundary->translate($positionTranslation->getX(), $positionTranslation->getY());
        }
        
        return $boundary;
    }
}