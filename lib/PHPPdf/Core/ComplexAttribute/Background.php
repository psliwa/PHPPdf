<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\ComplexAttribute;

use PHPPdf\Exception\InvalidArgumentException;

use PHPPdf\Core\Node\Page;
use PHPPdf\Core\UnitConverter;
use PHPPdf\Core\Document;
use PHPPdf\Core\Engine\GraphicsContext;
use PHPPdf\Util;
use PHPPdf\Core\Node\Node;

/**
 * Enhance node by drawing background
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Background extends ComplexAttribute
{
    const REPEAT_NONE = 0;
    const REPEAT_X = 1;
    const REPEAT_Y = 2;
    const REPEAT_ALL = 3;
    
    const POSITION_LEFT = 'left';
    const POSITION_RIGHT = 'right';
    const POSITION_TOP = 'top';
    const POSITION_BOTTOM = 'bottom';
    const POSITION_CENTER = 'center';

    private $image = null;
    private $repeat;
    private $useRealDimension;
    private $imageWidth = null;
    private $imageHeight = null;
    private $positionX = null;
    private $positionY = null;

    public function __construct($color = null, $image = null, $repeat = self::REPEAT_NONE, $radius = null, $useRealDimension = false, $imageWidth = null, $imageHeight = null, $positionX = self::POSITION_LEFT, $positionY = self::POSITION_TOP)
    {
        parent::__construct($color, $radius);

        $this->image = $image;
        $this->setRepeat($repeat);
        $this->useRealDimension = Util::convertBooleanValue($useRealDimension);
        $this->setImageDimension($imageWidth, $imageHeight);
        $this->setPosition($positionX, $positionY);
    }
    
    private function setPosition($positionX, $positionY)
    {
        $allowedXPositions = array(self::POSITION_LEFT, self::POSITION_CENTER, self::POSITION_RIGHT);
        if(!in_array($positionX, $allowedXPositions) && !$this->isNumeric($positionX))
        {
            throw new InvalidArgumentException(sprintf('Invalid x position "%s" for background, allowed values: %s or numeric value.', $positionX, implode(', ', $allowedXPositions)));
        }

        $allowedYPositions = array(self::POSITION_TOP, self::POSITION_CENTER, self::POSITION_BOTTOM);
        if(!in_array($positionY, $allowedYPositions) && !$this->isNumeric($positionY))
        {
            throw new InvalidArgumentException(sprintf('Invalid y position "%s" for background, allowed values: %s or numeric value.', $positionY, implode(', ', $allowedYPositions)));
        }

        $this->positionX = $positionX;
        $this->positionY = $positionY;
    }
    
    private function isNumeric($value)
    {
        if(is_numeric($value))
        {
            return true;
        }
        
        $numericValue = (string) (double) $value;
        
        return $numericValue === substr($value, 0, strlen($numericValue));
    }

    private function setRepeat($repeat)
    {
        if(!is_numeric($repeat))
        {
            $repeat = $this->getConstantValue('REPEAT', $repeat);
        }
        
        $this->repeat = $repeat;
    }
    
    public function getRepeat()
    {
        return $this->repeat;
    }
    
    private function setImageDimension($width, $height)
    {
        if($this->image === null)
        {
            return;
        }
        
        $this->imageWidth = $width;
        $this->imageHeight = $height;
    }

    public function getImage()
    {
        return $this->image;
    }

    protected function doEnhance($graphicsContext, Node $node, Document $document)
    {
        if($node->getShape() === Node::SHAPE_RECTANGLE)
        {
            $this->drawRectangleBackground($graphicsContext, $node, $document);
        }
        elseif($node->getShape() === Node::SHAPE_ELLIPSE)
        {
            $this->drawCircleBackground($graphicsContext, $node, $document);
        }
    }
    
    private function drawRectangleBackground(GraphicsContext $graphicsContext, Node $node, Document $document)
    {
        if($this->getColor() !== null)
        {
            $boundary = $this->getBoundary($node);
            if($this->getRadius() !== null)
            {
                $firstPoint = $boundary->getPoint(3);
                $diagonalPoint = $boundary->getPoint(1);
                
                $this->drawRoundedBoundary($graphicsContext, $firstPoint->getX(), $firstPoint->getY(), $diagonalPoint->getX(), $diagonalPoint->getY(), GraphicsContext::SHAPE_DRAW_FILL_AND_STROKE);
            }
            else
            {
                $this->drawBoundary($graphicsContext, $boundary, GraphicsContext::SHAPE_DRAW_FILL);
            }
        }

        $image = $this->getImage();
        $image = $image ? $document->createImage($image) : null;

        if($image !== null)
        {
            $positionTranslation = $node->getPositionTranslation();
            list($x, $y) = $this->getFirstPoint($node)->translate($positionTranslation->getX(), $positionTranslation->getY())->toArray();
            list($endX, $endY) = $this->getDiagonalPoint($node)->translate($positionTranslation->getX(), $positionTranslation->getY())->toArray();

            list($width, $height) = $this->getImageDimension($document, $image, $node);

            $graphicsContext->saveGS();
            $graphicsContext->clipRectangle($x, $y, $x+$this->getWidth($node), $y-$this->getHeight($node));
 
            $repeatX = $this->repeat & self::REPEAT_X;
            $repeatY = $this->repeat & self::REPEAT_Y;

            $currentX = $this->getXCoord($node, $width, $x, $document);
            $currentY = $y = $this->getYCoord($node, $height, $y, $document);

            do
            {
                $currentY = $y;
                do
                {
                    $graphicsContext->drawImage($image, $currentX, $currentY-$height, $currentX+$width, $currentY);
                    $currentY -= $height;
                }
                while($repeatY && $currentY > $endY);

                $currentX += $width;
            }
            while($repeatX && $currentX < $endX);
            
            $graphicsContext->restoreGS();
        }
    }
    
    private function getXCoord(Node $node, $width, $x, UnitConverter $converter)
    {
        switch($this->positionX)
        {
            case self::POSITION_RIGHT:
                $realWidth = $node->getDiagonalPoint()->getX() - $node->getFirstPoint()->getX();
                return ($x + $realWidth) - $width;
            case self::POSITION_CENTER:
                $realWidth = $node->getDiagonalPoint()->getX() - $node->getFirstPoint()->getX();
                return ($x + $realWidth/2) - $width/2;
            case self::POSITION_LEFT:
                return $x;
            default:
                return $x + $converter->convertUnit($converter->convertPercentageValue($this->positionX, $node->getWidth()));
        }
    }
    
    private function getYCoord(Node $node, $height, $y, UnitConverter $converter)
    {
        switch($this->positionY)
        {
            case self::POSITION_BOTTOM:
                $realHeight = $node->getFirstPoint()->getY() - $node->getDiagonalPoint()->getY();
                return ($y - $realHeight) + $height;
            case self::POSITION_CENTER:
                $realHeight = $node->getFirstPoint()->getY() - $node->getDiagonalPoint()->getY();
                return ($y - $realHeight/2) + $height/2;
            case self::POSITION_TOP:
                return $y;
            default:
                return $y - $converter->convertUnit($converter->convertPercentageValue($this->positionY, $node->getHeight()));
        }
    }
    
    private function getImageDimension(UnitConverter $converter, $image, Node $node)
    {
        $width = $converter->convertUnit($this->imageWidth);
        $height = $converter->convertUnit($this->imageHeight);

        if(!$width && !$height)
        {
            return array($image->getOriginalWidth(), $image->getOriginalHeight());
        }
        
        list($width, $height) = $this->convertPercentageDimension($converter, $node, $width, $height);

        $ratio = $image->getOriginalWidth() / $image->getOriginalHeight();
            
        list($width, $height) = Util::calculateDependantSizes($width, $height, $ratio);
        
        return array($width, $height);
    }
    
    private function convertPercentageDimension(UnitConverter $converter, Node $node, $width, $height)
    {
        $width = $converter->convertPercentageValue($width, $this->getWidth($node));
        $height = $converter->convertPercentageValue($height, $this->getHeight($node));

        return array($width, $height);
    }

    private function getFirstPoint(Node $node)
    {
        if($this->useRealDimension)
        {
            return $node->getRealFirstPoint();
        }
        
        return $node->getFirstPoint();
    }
    
    private function getDiagonalPoint(Node $node)
    {
        if($this->useRealDimension)
        {
            return $node->getRealDiagonalPoint();
        }
        
        return $node->getDiagonalPoint();
    }
    
    private function getBoundary(Node $node)
    {        
        $boundary = $this->useRealDimension ? $node->getRealBoundary() : $node->getBoundary();
        
        return $this->getTranslationAwareBoundary($node, $boundary);
    }
    
    private function getWidth(Node $node)
    {
        if($this->useRealDimension)
        {
            return $node->getRealWidth();
        }
        
        return $node->getWidth();
    }

    private function getHeight(Node $node)
    {
        if($this->useRealDimension)
        {
            return $node->getRealHeight();
        }
        
        return $node->getHeight();
    }
    
    private function drawCircleBackground(GraphicsContext $gc, Node $node, Document $document)
    {
        $point = $node->getMiddlePoint();
        
        $translation = $node->getPositionTranslation();
        
        if(!$translation->isZero())
        {
            $point = $point->translate($translation->getX(), $translation->getY());
        }
        
        $this->drawCircle($gc, $node->getAttribute('radius'), $point->getX(), $point->getY(), GraphicsContext::SHAPE_DRAW_FILL);
    }
}