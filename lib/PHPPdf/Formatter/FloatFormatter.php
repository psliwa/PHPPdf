<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Formatter;

use PHPPdf\Node\Node,
    PHPPdf\Node\Text,
    PHPPdf\Document,
    PHPPdf\Util\Boundary;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 * @todo refactoring
 */
class FloatFormatter extends BaseFormatter
{
    public function format(Node $node, Document $document)
    {
        $children = $node->getChildren();
        $attributesSnapshot = $node->getAttributesSnapshot();
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

                $this->setNodesWithFloatPosition($child, $x, $y, $sibling);
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
                    if($child->getDisplay() === Node::DISPLAY_INLINE && $previousSiblingWithMinBottomYCoord->getDisplay() === Node::DISPLAY_INLINE)
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

        if($positionCorrection && $parentBottomYCoord && !$node->getAttribute('static-size'))
        {
            $parentTranslate = $node->getDiagonalPoint()->getY() - $parentBottomYCoord;
            $newHeight = $node->getHeight() + $parentTranslate;
            $oldHeight = isset($attributesSnapshot['height']) ? $attributesSnapshot['height'] : 0;

            if($newHeight < $oldHeight)
            {
                $parentTranslate += $oldHeight - $newHeight;
                $newHeight = $oldHeight;
            }

            $node->setHeight($newHeight);
            $node->getBoundary()->pointTranslate(2, 0, $parentTranslate);
            $node->getBoundary()->pointTranslate(3, 0, $parentTranslate);
        }
    }

    private function hasFloat(Node $node)
    {
        return $node->getFloat() !== Node::FLOAT_NONE;
    }

    private function getPreviousSiblingWithFloat(Node $node, $float)
    {
        $siblings = $node->getSiblings();
        $floatedSibling = null;
        for($i=0, $count=count($siblings); $i<$count && $siblings[$i] !== $node; $i++)
        {
            if($siblings[$i]->getFloat() === $float)
            {
                $floatedSibling = $siblings[$i];
            }
            elseif($siblings[$i]->getFloat() === Node::FLOAT_NONE)
            {
                $floatedSibling = null;
            }
        }

        return $floatedSibling;
    }

    private function setNodesWithFloatPosition(Node $node, &$preferredXCoord, &$preferredYCoord, Node $previousSiblingWithTheSameFloat = null)
    {
        $sibling = $previousSiblingWithTheSameFloat;
        $parent = $node->getParent();

        if($sibling)
        {
            $originalPreferredXCoord = $preferredXCoord;
            if($node->getFloat() === Node::FLOAT_LEFT)
            {
                $preferredXCoord = $sibling->getDiagonalPoint()->getX() + $sibling->getMarginRight() + $node->getMarginLeft() + $node->getPaddingLeft();
            }
            else
            {
                $preferredXCoord = $sibling->getFirstPoint()->getX() - $sibling->getMarginLeft() - $node->getMarginRight() - $node->getWidth() - $node->getPaddingRight() ;
            }

            list(,$preferredYCoord) = $sibling->getStartDrawingPoint();

            if($preferredXCoord < $parent->getFirstPoint()->getX() || ($preferredXCoord + $node->getWidth()) > $parent->getDiagonalPoint()->getX())
            {
                $overflowed = true;
            }
            else
            {
                $dummyBoundary = $this->createBoundary($node, $preferredXCoord, $preferredYCoord);

                $siblings = $node->getSiblings();
                $overflowed = false;
                for($i=0, $count=count($siblings); $i<$count && $siblings[$i] !== $node; $i++)
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
                $preferredYCoord = $sibling->getDiagonalPoint()->getY() - $node->getPaddingTop() - ($sibling->getMarginBottom() + $node->getMarginTop());
                $preferredXCoord = $this->correctXCoordWithParent($node, $sibling);
            }
        }
        else
        {
            $previousSibling = $node->getPreviousSibling();

            $preferredXCoord = $this->correctXCoordWithParent($node);
            
            if($previousSibling && $previousSibling->getFloat() !== Node::FLOAT_NONE)
            {
                $yCoord = $preferredYCoord + $previousSibling->getHeight() + $node->getMarginTop() + $previousSibling->getMarginBottom();
                
                $boundary = $this->createBoundary($node, $preferredXCoord, $yCoord);
                
                if(!$boundary->intersects($previousSibling->getBoundary()))
                {
                    $preferredYCoord += $previousSibling->getHeight() + $node->getMarginTop() + $previousSibling->getMarginBottom();
                }
            }
        }
    }
    
    /**
     * @return Boundary
     */
    private function createBoundary(Node $node, $preferredXCoord, $preferredYCoord)
    {
        $dummyBoundary = new Boundary();
        $dummyBoundary->setNext($preferredXCoord, $preferredYCoord)
                      ->setNext($preferredXCoord + $node->getWidth(), $preferredYCoord)
                      ->setNext($preferredXCoord + $node->getWidth(), $preferredYCoord - $node->getHeight())
                      ->setNext($preferredXCoord, $preferredYCoord - $node->getHeight());
                      
        return $dummyBoundary;
    }

    private function correctXCoordWithParent(Node $node)
    {
        $parent = $node->getParent();
        if($node->getFloat() === Node::FLOAT_LEFT)
        {
            $preferredXCoord = $parent->getFirstPoint()->getX() + $parent->getPaddingLeft() + $node->getMarginLeft() + $node->getPaddingLeft();
        }
        else
        {
            $preferredXCoord = $parent->getDiagonalPoint()->getX() - $node->getWidth() + $node->getPaddingLeft() - $node->getMarginRight();
        }

        return $preferredXCoord;
    }
}