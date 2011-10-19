<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Formatter;

use PHPPdf\Core\Point;

use PHPPdf\Core\Node\Node,
    PHPPdf\Core\Document,
    PHPPdf\Core\Boundary,
    PHPPdf\Core\Node\ColumnableContainer,
    PHPPdf\Core\Node\Container;

/**
 * Formats columnable container, breaks containers into columns. 
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ColumnBreakingFormatter extends BaseFormatter
{
    private $staticBreakYCoord = null;
    private $stopBreaking = false;
    
    private $lastVerticalTranslation = 0;
    
    public function format(Node $node, Document $document)
    {
        $this->lastVerticalTranslation = 0;

        $nodes = $node instanceof ColumnableContainer ? array($node) : $node->getChildren();
        
        foreach($nodes as $node)
        {
            if($node instanceof ColumnableContainer)
            {
                $container = $this->moveAllChildrenToSingleContainer($node);
                
                $this->breakContainerIntoColumns($node, $container);
                
                $this->resizeColumnableContainer($node);
                
                $this->staticBreakYCoord = null;
                $this->stopBreaking = false;
            }
        }
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
    
    private function breakContainerIntoColumns(ColumnableContainer $columnableContainer, Container $container)
    {
        $numberOfBreaks = 0;
        $breakYCoord = $this->getBreakYCoord($columnableContainer, $numberOfBreaks++, $container);
        
        do
        {
            if($this->shouldBeBroken($container, $breakYCoord))
            {
                $container = $this->breakContainer($container, $breakYCoord, $numberOfBreaks);
                $breakYCoord = $this->getBreakYCoord($columnableContainer, $numberOfBreaks++, $container);
            }
            else
            {
                $container = null;
            }
            
        }
        while($container);
    }
    
    private function getBreakYCoord(ColumnableContainer $columnableContainer, $numberOfBreaks, Container $container = null)
    {
        $numberOfColumns = $columnableContainer->getAttribute('number-of-columns');
        
        $rowNumber = $this->getNumberOfRow($columnableContainer, $numberOfBreaks);
        $columnNumber = $this->getNumberOfColumn($columnableContainer, $numberOfBreaks);
        
        if($this->staticBreakYCoord !== null)
        {
            if($columnNumber == ($numberOfColumns - 1))
            {
                $this->stopBreaking = true;
            }
            return $this->staticBreakYCoord;
        }
        
        $page = $columnableContainer->getPage();
        
        $container = $container ? : $columnableContainer;
        
        $forcedBreakYCoord = null;
        
        foreach($container->getChildren() as $child)
        {
            if($child->getAttribute('break'))
            {                
                $forcedBreakYCoord = $child->getDiagonalPoint()->getY();
                break;
            }
        }        
        
        $minBreakYCoord = $this->getDiagonalYCoordOfColumn($columnableContainer, $container, $columnNumber, $rowNumber);
        
        if($forcedBreakYCoord === null)
        {
            return $minBreakYCoord;
        }

        return max($minBreakYCoord, $forcedBreakYCoord);
    }
    
    private function getNumberOfRow(ColumnableContainer $container, $numberOfBreaks)
    {
        $numberOfColumns = $container->getAttribute('number-of-columns');
        
        return floor($numberOfBreaks/$numberOfColumns);
    }
    
    private function getNumberOfColumn(ColumnableContainer $columnableContainer, $numberOfBreaks)
    {
        $numberOfColumns = $columnableContainer->getAttribute('number-of-columns');
        
        return $numberOfBreaks % $numberOfColumns;
    }
    
    private function getDiagonalYCoordOfColumn($columnableContainer, $container, $columnNumber, $rowNumber)
    {
        $numberOfColumns = $columnableContainer->getAttribute('number-of-columns');
        
        if($this->shouldColumnsBeEqual($columnableContainer, $container, $columnNumber, $rowNumber))
        {
            $prefferedContainerSize = $container->getHeight() / $numberOfColumns;
            
            $this->staticBreakYCoord = $container->getFirstPoint()->getY() - $prefferedContainerSize;
            
            return $this->staticBreakYCoord;
        }
        else
        {
            return $this->getCurrentPageDiagonalYCoord($columnableContainer, $rowNumber);
        }
    }
    
    private function getCurrentPageDiagonalYCoord(ColumnableContainer $columnableContainer, $rowNumber)
    {
        $page = $columnableContainer->getPage();

        return $this->getPageDiagonalYCoord($columnableContainer) - ($rowNumber)*$page->getHeight();
    }
    
    private function getPageDiagonalYCoord(ColumnableContainer $columnableContainer)
    {
        $page = $columnableContainer->getPage();
        
        $numberOfPage = (int) (($page->getFirstPoint()->getY()-$columnableContainer->getFirstPoint()->getY()) / $page->getHeight());
        
        return $page->getDiagonalPoint()->getY() - $numberOfPage*$page->getHeight();
    }
    
    private function shouldColumnsBeEqual(ColumnableContainer $columnableContainer, Container $container, $columnNumber, $rowNumber)
    {       
        $parent = $columnableContainer->getParent();
        $height = $container->getFirstPoint()->getY() - ($this->getPageDiagonalYCoord($columnableContainer) - ($rowNumber*$parent->getHeight()));
        $freeSpace = $height * ($columnableContainer->getAttribute('number-of-columns') - $columnNumber);
        
        return $columnNumber == 0 && $columnableContainer->getAttribute('equals-columns') && $container->getHeight() < $freeSpace;
    }
    
    private function shouldBeBroken(Container $container, $pageYCoordEnd)
    {
        if($this->stopBreaking)
        {
            return false;
        }
        
        $yEnd = $container->getDiagonalPoint()->getY();

        return ($yEnd < $pageYCoordEnd);
    }
    
    private function breakContainer(Container $container, $breakYCoord, $numberOfBreaks)
    {
        $breakPoint = $container->getFirstPoint()->getY() - $breakYCoord;
        
        $productOfBroke = $container->breakAt($breakPoint);
        
        if($productOfBroke)
        {
            $this->resizeAndMoveContainersToColumnHeight($container, $productOfBroke, $numberOfBreaks);
            
            $container->getParent()->add($productOfBroke);
            
            $this->translateProductOfBroke($productOfBroke, $container);
            
            return $productOfBroke;
        }
    }
    
    private function resizeAndMoveContainersToColumnHeight(Container $originalContainer, Container $productOfBroke, $numberOfBreaks)
    {
        $numberOfBreaks--;
        $columnableContainer = $originalContainer->getParent();
        $numberOfRow = $this->getNumberOfRow($columnableContainer, $numberOfBreaks);
        $numberOfColumn = $this->getNumberOfColumn($columnableContainer, $numberOfBreaks);        
        
        $yCoord = $this->getCurrentPageDiagonalYCoord($columnableContainer, $numberOfRow);
        
        $yCoord = $this->staticBreakYCoord !== null && $this->staticBreakYCoord > $yCoord ? $this->staticBreakYCoord : $yCoord;
        
        $enlarge = $originalContainer->getDiagonalPoint()->getY() - $yCoord;
        
        if($enlarge > 0)
        {
            $originalContainer->resize(0, $enlarge);
            $productOfBroke->translate(0, $enlarge);
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
        $originalHeight = $columnableContainer->getHeight();
        
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
        
        $this->lastVerticalTranslation = $columnableContainer->getHeight() - $originalHeight;
    }
    
    public function getLastVerticalTranslation()
    {
        return $this->lastVerticalTranslation;
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