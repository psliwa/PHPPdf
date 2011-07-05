<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Formatter;

use PHPPdf\Glyph\Glyph,
    PHPPdf\Document,
    PHPPdf\Util\Boundary,
    PHPPdf\Glyph\ColumnableContainer,
    PHPPdf\Glyph\Container;

/**
 * Formats columnable container, breaks containers into columns. 
 * 
 * TODO: rename
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ColumnDivertingFormatter extends BaseFormatter
{
    public function format(Glyph $glyph, Document $document)
    {
        $container = $this->moveAllChildrenToSingleContainer($glyph);
        
        $this->splitContainerIntoColumns($glyph, $container);
        
        $this->resizeColumnableContainer($glyph);
    }

    private function moveAllChildrenToSingleContainer(ColumnableContainer $columnableContainer)
    {
        $container = new Container();
        
        foreach($columnableContainer->getChildren() as $child)
        {
            $container->add($child);
        }
        
        $columnableContainer->removeAll();
        
        $columnableContainer->add($container);
        
        $this->setDimension($columnableContainer, $container);
        
        return $container;        
    }
    
    private function setDimension(ColumnableContainer $columnableContainer, Container $container)
    {
        $container->setWidth($columnableContainer->getWidth());
        $container->setHeight($columnableContainer->getHeight());
        $this->setPointsToBoundary($columnableContainer->getBoundary(), $container->getBoundary());
    }
    
    private function setPointsToBoundary(Boundary $source, Boundary $destination)
    {
        $destination->setNext($source[0])
                    ->setNext($source[1])
                    ->setNext($source[2])
                    ->setNext($source[3])
                    ->close();
    }
    
    private function splitContainerIntoColumns(ColumnableContainer $columnableContainer, Container $container)
    {
        $numberOfBreaks = 0;
        $breakYCoord = $this->getBreakYCoord($columnableContainer, $numberOfBreaks++);
        
        do
        {
            if($this->shouldBeBroken($container, $breakYCoord))
            {
                $container = $this->breakContainer($container, $breakYCoord);
                $childHasBeenSplitted = true;

                $breakYCoord = $this->getBreakYCoord($columnableContainer, $numberOfBreaks++);
            }
            else
            {
                $container = null;
            }
            
        }
        while($container);
    }
    
    private function getBreakYCoord(ColumnableContainer $columnableContainer, $numberOfBreaks)
    {
        $numberOfColumns = $columnableContainer->getAttribute('number-of-columns');
        
        $numberOfRow = floor($numberOfBreaks/$numberOfColumns) + 1;
        
        $page = $columnableContainer->getPage();
        
        return $page->getDiagonalPoint()->getY() - ($numberOfRow-1)*$page->getHeight();
    }
    
    private function shouldBeBroken(Container $container, $pageYCoordEnd)
    {
        $yEnd = $container->getDiagonalPoint()->getY();

        return ($yEnd < $pageYCoordEnd);
    }
    
    private function breakContainer(Container $container, $breakYCoord)
    {
        $breakPoint = $container->getFirstPoint()->getY() - $breakYCoord;
        $originalHeightOfContainer = $container->getHeight();
        
        $productOfBroke = $container->split($breakPoint);
        
        if($productOfBroke)
        {
            $gapBetweenBottomOfContainerAndBreakYCoord = $container->getDiagonalPoint()->getY() - $breakYCoord;
            
            $gap = $originalHeightOfContainer - ($container->getHeight() + $productOfBroke->getHeight());
            
            $container->resize(0, $gap);
            
            $container->getParent()->add($productOfBroke);
            
            $this->translateProductOfBroke($productOfBroke, $container);
            
            return $productOfBroke;
        }
    }
    
    private function translateProductOfBroke(Container $productOfBroke, Container $originalContainer)
    {
        $columnableContainer = $originalContainer->getParent();
        
        $numberOfContainers = count($columnableContainer->getChildren());
        $numberOfColumns = $columnableContainer->getAttribute('number-of-columns');
        
        $isInTheSameRowAsOriginalContainer = $numberOfContainers % $numberOfColumns != 1;
        
        if($isInTheSameRowAsOriginalContainer)
        {
            $xCoordTranslate = $originalContainer->getWidth() + $columnableContainer->getAttribute('margin-between-columns');
            $firstPoint = $originalContainer->getFirstPoint()->translate($xCoordTranslate, 0);
        }
        else
        {
            $xCoordTranslate = $numberOfColumns*$originalContainer->getWidth() + ($numberOfColumns-1)*$columnableContainer->getAttribute('margin-between-columns');
            $firstPoint = $originalContainer->getDiagonalPoint()->translate(-$xCoordTranslate, 0);
        }
        
        $xCoordTranslate = $firstPoint->getX() - $productOfBroke->getFirstPoint()->getX();
        $yCoordTranslate = $productOfBroke->getFirstPoint()->getY() - $firstPoint->getY();
        $productOfBroke->translate($xCoordTranslate, $yCoordTranslate);
    }
    
    private function resizeColumnableContainer(ColumnableContainer $columnableContainer)
    {
        $containers = $columnableContainer->getChildren();

        $parentWidth = $columnableContainer->getParent()->getWidth();
        $bottomYCoord = $this->getMinBottomYCoordOfContainer($containers);
        
        $firstPoint = $columnableContainer->getFirstPoint();
        $boundary = $columnableContainer->getBoundary();
        $boundary->reset();
        $boundary->setNext($firstPoint)
                 ->setNext($firstPoint->translate($parentWidth, 0))
                 ->setNext($firstPoint->translate($parentWidth, -($bottomYCoord - $firstPoint->getY())))
                 ->setNext($firstPoint->translate(0, -($bottomYCoord - $firstPoint->getY())))
                 ->close();

        $columnableContainer->setWidth($parentWidth);
        $columnableContainer->setHeight($firstPoint->getY() - $columnableContainer->getDiagonalPoint()->getY());
    }
    
    private function getMinBottomYCoordOfContainer(array $containers)
    {
        $min = PHP_INT_MAX;
        
        foreach($containers as $container)
        {
            $min = min($container->getDiagonalPoint()->getY(), $min);
        }
        
        return $min;
    }
}