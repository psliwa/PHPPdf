<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Formatter;

use PHPPdf\Glyph\Glyph,
    PHPPdf\Glyph\Text,
    PHPPdf\Document,
    PHPPdf\Util\Boundary;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 * @todo refactoring
 */
class FloatFormatter extends BaseFormatter
{
    public function format(Glyph $glyph, Document $document)
    {
        $children = $glyph->getChildren();
        $attributesSnapshot = $glyph->getAttributesSnapshot();
        $parentBottomYCoord = null;

        $positionCorrection = false;
        foreach($children as $child)
        {
            $translateX = $translateY = 0;
            list($x, $y) = $child->getStartDrawingPoint();

            if($this->hasFloat($child))
            {
                $sibling = $this->getPreviousSiblingWithFloat($child, $child->getFloat());

                list($orgX, $orgY) = $child->getStartDrawingPoint();

                if(!$sibling)
                {
                    $previousSibling = $child->getPreviousSibling();
                    if($previousSibling)
                    {
                        $y = $previousSibling->getDiagonalPoint()->getY() - $previousSibling->getMarginBottom() - $child->getMarginTop() - $child->getPaddingTop();
                    }
                }

                $this->setGlyphsWithFloatPosition($child, $x, $y, $sibling);
                $translateX = $x - $orgX;
                $translateY = -($y - $orgY);

            }
            elseif($positionCorrection)
            {
                $siblings = $child->getSiblings();
                
                $minYCoord = null;
                $previousSiblingWithMinBottomYCoord = null;
                for($i=0, $count = count($siblings); $i<$count && $siblings[$i] !== $child; $i++)
                {
                    $previousSibling = $siblings[$i];
                    $bottomYCoord = $previousSibling->getDiagonalPoint()->getY() - $previousSibling->getMarginBottom();
                    
                    if($minYCoord === null || $bottomYCoord < $minYCoord)
                    {
                        $minYCoord = $bottomYCoord;
                        $previousSiblingWithMinBottomYCoord = $previousSibling;
                    }                    
                }
                if($minYCoord !== null)
                {
                    $translateY = -($minYCoord - $y - $child->getMarginTop() - $child->getPaddingTop());
                    if($child->getDisplay() === Glyph::DISPLAY_INLINE && $previousSiblingWithMinBottomYCoord->getDisplay() === Glyph::DISPLAY_INLINE)
                    {
                        $translateY -= $child instanceof Text ? $child->getLineHeightRecursively() : $child->getHeight();
                    }
                }
            }

            if($translateX || $translateY)
            {
                $child->translate($translateX, $translateY);
            }

            $childBottomYCoord = $child->getDiagonalPoint()->getY() - $child->getMarginBottom();
            if($parentBottomYCoord === null || $childBottomYCoord < $parentBottomYCoord)
            {
                $parentBottomYCoord = $childBottomYCoord;
            }

            if($translateY)
            {
                $positionCorrection = true;
            }
        }

        if($positionCorrection && $parentBottomYCoord && !$glyph->getAttribute('static-size'))
        {
            $parentTranslate = $glyph->getDiagonalPoint()->getY() - $parentBottomYCoord;
            $newHeight = $glyph->getHeight() + $parentTranslate;
            $oldHeight = isset($attributesSnapshot['height']) ? $attributesSnapshot['height'] : 0;

            if($newHeight < $oldHeight)
            {
                $parentTranslate += $oldHeight - $newHeight;
                $newHeight = $oldHeight;
            }

            $glyph->setHeight($newHeight);
            $glyph->getBoundary()->pointTranslate(2, 0, $parentTranslate);
            $glyph->getBoundary()->pointTranslate(3, 0, $parentTranslate);
        }
    }

    private function hasFloat(Glyph $glyph)
    {
        return $glyph->getFloat() !== Glyph::FLOAT_NONE;
    }

    private function getPreviousSiblingWithFloat(Glyph $glyph, $float)
    {
        $siblings = $glyph->getSiblings();
        $floatedSibling = null;
        for($i=0, $count=count($siblings); $i<$count && $siblings[$i] !== $glyph; $i++)
        {
            if($siblings[$i]->getFloat() === $float)
            {
                $floatedSibling = $siblings[$i];
            }
            elseif($siblings[$i]->getFloat() === Glyph::FLOAT_NONE)
            {
                $floatedSibling = null;
            }
        }

        return $floatedSibling;
    }

    private function setGlyphsWithFloatPosition(Glyph $glyph, &$preferredXCoord, &$preferredYCoord, Glyph $previousSiblingWithTheSameFloat = null)
    {
        $sibling = $previousSiblingWithTheSameFloat;
        $parent = $glyph->getParent();

        if($sibling)
        {
            $originalPreferredXCoord = $preferredXCoord;
            if($glyph->getFloat() === Glyph::FLOAT_LEFT)
            {
                $preferredXCoord = $sibling->getDiagonalPoint()->getX() + $sibling->getMarginRight() + $glyph->getMarginLeft() + $glyph->getPaddingLeft();
            }
            else
            {
                $preferredXCoord = $sibling->getFirstPoint()->getX() - $sibling->getMarginLeft() - $glyph->getMarginRight() - $glyph->getWidth() - $glyph->getPaddingRight() ;
            }

            list(,$preferredYCoord) = $sibling->getStartDrawingPoint();

            if($preferredXCoord < $parent->getFirstPoint()->getX() || ($preferredXCoord + $glyph->getWidth()) > $parent->getDiagonalPoint()->getX())
            {
                $overflowed = true;
            }
            else
            {
                $dummyBoundary = $this->createBoundary($glyph, $preferredXCoord, $preferredYCoord);

                $siblings = $glyph->getSiblings();
                $overflowed = false;
                for($i=0, $count=count($siblings); $i<$count && $siblings[$i] !== $glyph; $i++)
                {
                    if($dummyBoundary->intersects($siblings[$i]->getBoundary()))
                    {
                        $overflowed = true;
                        break;
                    }
                }
            }

            if($overflowed)
            {
                $preferredYCoord = $sibling->getDiagonalPoint()->getY() - $glyph->getPaddingTop() - ($sibling->getMarginBottom() + $glyph->getMarginTop());
                $preferredXCoord = $this->correctXCoordWithParent($glyph, $sibling);
            }
        }
        else
        {
            $previousSibling = $glyph->getPreviousSibling();

            $preferredXCoord = $this->correctXCoordWithParent($glyph);
            
            if($previousSibling && $previousSibling->getFloat() !== Glyph::FLOAT_NONE)
            {
                $yCoord = $preferredYCoord + $previousSibling->getHeight() + $glyph->getMarginTop() + $previousSibling->getMarginBottom();
                
                $boundary = $this->createBoundary($glyph, $preferredXCoord, $yCoord);
                
                if(!$boundary->intersects($previousSibling->getBoundary()))
                {
                    $preferredYCoord += $previousSibling->getHeight() + $glyph->getMarginTop() + $previousSibling->getMarginBottom();
                }
            }
        }
    }
    
    /**
     * @return Boundary
     */
    private function createBoundary(Glyph $glyph, $preferredXCoord, $preferredYCoord)
    {
        $dummyBoundary = new Boundary();
        $dummyBoundary->setNext($preferredXCoord, $preferredYCoord)
                      ->setNext($preferredXCoord + $glyph->getWidth(), $preferredYCoord)
                      ->setNext($preferredXCoord + $glyph->getWidth(), $preferredYCoord - $glyph->getHeight())
                      ->setNext($preferredXCoord, $preferredYCoord - $glyph->getHeight());
                      
        return $dummyBoundary;
    }

    private function correctXCoordWithParent(Glyph $glyph)
    {
        $parent = $glyph->getParent();
        if($glyph->getFloat() === Glyph::FLOAT_LEFT)
        {
            $preferredXCoord = $parent->getFirstPoint()->getX() + $parent->getPaddingLeft() + $glyph->getMarginLeft() + $glyph->getPaddingLeft();
        }
        else
        {
            $preferredXCoord = $parent->getDiagonalPoint()->getX() - $glyph->getWidth() + $glyph->getPaddingLeft() - $glyph->getMarginRight();
        }

        return $preferredXCoord;
    }
}