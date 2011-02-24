<?php

namespace PHPPdf\Formatter;

use PHPPdf\Glyph\Glyph,
    PHPPdf\Glyph\AbstractGlyph,
    PHPPdf\Glyph\Text,
    PHPPdf\Util\Boundary;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 * @todo refactoring
 */
class FloatFormatter extends BaseFormatter
{
    public function postFormat(Glyph $glyph)
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
                        $y = $previousSibling->getBoundary()->getDiagonalPoint()->getY() - $previousSibling->getMarginBottom() - $child->getMarginTop() - $child->getPaddingTop();
                    }
                }

                $this->setGlyphsWithFloatPosition($child, $x, $y, $sibling);
                $translateX = $x - $orgX;
                $translateY = -($y - $orgY);

            }
            elseif($positionCorrection)
            {
                $previousSibling = $child->getPreviousSibling();
                if($previousSibling)
                {
                    $siblingY = $previousSibling->getBoundary()->getDiagonalPoint()->getY();
                    $translateY = -($siblingY - $y - $previousSibling->getMarginBottom() - $child->getMarginTop() - $child->getPaddingTop());
                    if($child->getDisplay() === AbstractGlyph::DISPLAY_INLINE)
                    {
                        $translateY -= $child instanceof Text ? $child->getLineHeight() : $child->getHeight();
                    }
                }
            }

            if($translateX || $translateY)
            {
                $child->translate($translateX, $translateY);
            }

            $childBottomYCoord = $child->getBoundary()->getDiagonalPoint()->getY() - $child->getMarginBottom();
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
            $parentTranslate = $glyph->getBoundary()->getDiagonalPoint()->getY() - $parentBottomYCoord;
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
        return $glyph->getFloat() !== AbstractGlyph::FLOAT_NONE;
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
            elseif($siblings[$i]->getFloat() === AbstractGlyph::FLOAT_NONE)
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
            if($glyph->getFloat() === AbstractGlyph::FLOAT_LEFT)
            {
                $preferredXCoord = $sibling->getBoundary()->getDiagonalPoint()->getX() + $sibling->getMarginRight() + $glyph->getMarginLeft() + $glyph->getPaddingLeft();
            }
            else
            {
                $preferredXCoord = $sibling->getBoundary()->getFirstPoint()->getX() - $sibling->getMarginLeft() - $glyph->getMarginRight() - $glyph->getWidth() - $glyph->getPaddingRight() ;
            }

            list(,$preferredYCoord) = $sibling->getStartDrawingPoint();

            if($preferredXCoord < $parent->getBoundary()->getFirstPoint()->getX() || ($preferredXCoord + $glyph->getWidth()) > $parent->getBoundary()->getDiagonalPoint()->getX())
            {
                $overflowed = true;
            }
            else
            {
                $dummyBoundary = new Boundary();
                $dummyBoundary->setNext($preferredXCoord, $preferredYCoord)
                              ->setNext($preferredXCoord + $glyph->getWidth(), $preferredYCoord)
                              ->setNext($preferredXCoord + $glyph->getWidth(), $preferredYCoord - $glyph->getHeight())
                              ->setNext($preferredXCoord, $preferredYCoord - $glyph->getHeight());

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
                $preferredYCoord = $sibling->getBoundary()->getDiagonalPoint()->getY() - ($sibling->getMarginBottom() + $glyph->getMarginTop());
                $preferredXCoord = $this->correctXCoordWithParent($glyph, $sibling);
            }
        }
        else
        {
            $previousSibling = $glyph->getPreviousSibling();

            if($previousSibling && $previousSibling->getFloat() !== AbstractGlyph::FLOAT_NONE)
            {
                $preferredYCoord += $previousSibling->getHeight() + $glyph->getMarginTop() + $previousSibling->getMarginBottom();
            }

            $preferredXCoord = $this->correctXCoordWithParent($glyph);
        }
    }

    private function correctXCoordWithParent(Glyph $glyph)
    {
        $parent = $glyph->getParent();
        if($glyph->getFloat() === AbstractGlyph::FLOAT_LEFT)
        {
            $preferredXCoord = $parent->getBoundary()->getFirstPoint()->getX() + $parent->getPaddingLeft() + $glyph->getMarginLeft() + $glyph->getPaddingLeft();
        }
        else
        {
            $preferredXCoord = $parent->getBoundary()->getDiagonalPoint()->getX() - $glyph->getWidth() + $glyph->getPaddingLeft();
        }

        return $preferredXCoord;
    }
}