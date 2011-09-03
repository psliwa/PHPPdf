<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Formatter;

use PHPPdf\Node\Node,
    PHPPdf\Document;

/**
 * TODO: refactoring and rename
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class PageDivertingFormatter extends BaseFormatter
{
    protected $node;
    protected $totalVerticalTranslation = 0;
    
    public function format(Node $node, Document $document)
    {
        $columnFormatter = new ColumnDivertingFormatter();

        $this->node = $node;
        $this->totalVerticalTranslation = 0;

        $children = $this->node->getChildren();
        foreach($this->node->getChildren() as $child)
        {
            $child->translate(0, -$this->totalVerticalTranslation);
            
            $columnFormatter->format($child, $document);   

            $verticalTranslation = $columnFormatter->getLastVerticalTranslation();
            
            $this->splitChildIfNecessary($child);
            
            $this->totalVerticalTranslation += -$verticalTranslation;
        }
        
        foreach($node->getPages() as $page)
        {
            $page->preFormat($document);
        }
    }

    private function splitChildIfNecessary(Node $node)
    {
        $childHasBeenSplitted = false;
        $childMayBeSplitted = true;
        
        if($this->shouldParentBeAutomaticallyBroken($node))
        {
            $pageYCoordEnd = $node->getDiagonalPoint()->getY() + 1;
        }
        else
        {
            $pageYCoordEnd = $this->getPageYCoordEnd();
        }

        do
        {
            if($this->shouldBeSplited($node, $pageYCoordEnd))
            {
                $node = $this->splitChildAndGetProductOfSplitting($node);
                $childHasBeenSplitted = true;
            }
            else
            {
                if(!$childHasBeenSplitted)
                {
                    $this->addToSubjectOfSplitting($node);
                }

                $childMayBeSplitted = false;
            }
            
            $pageYCoordEnd = $this->getPageYCoordEnd();
        }
        while($childMayBeSplitted);
    }
    
    private function shouldParentBeAutomaticallyBroken(Node $node)
    {
        return $node->getAttribute('break');
    }
    
    private function getPageYCoordEnd()
    {
        return $this->node->getPage()->getDiagonalPoint()->getY();
    }
    
    private function splitChildAndGetProductOfSplitting(Node $node)
    {
        $originalHeight = $node->getFirstPoint()->getY() - $node->getDiagonalPoint()->getY();
        $nodeYCoordStart = $this->getChildYCoordOfFirstPoint($node);
        $end = $this->getPageYCoordEnd();
        $splitLine = $nodeYCoordStart - $end;
        $splittedNode = $node->split($splitLine);

        $gapBeetwenBottomOfOriginalNodeAndEndOfPage = 0;

        if($splittedNode)
        {           
            $gapBeetwenBottomOfOriginalNodeAndEndOfPage = $node->getDiagonalPoint()->getY() - $end;

            $gap = $originalHeight - (($node->getFirstPoint()->getY() - $node->getDiagonalPoint()->getY()) + ($splittedNode->getFirstPoint()->getY() - $splittedNode->getDiagonalPoint()->getY()));
            $this->totalVerticalTranslation += $gap;

            $nodeYCoordStart = $splittedNode->getFirstPoint()->getY();
            $this->addToSubjectOfSplitting($node);
            $node = $splittedNode;
        }

        $this->breakSubjectOfSplittingIncraseTranslation($node, $nodeYCoordStart, $gapBeetwenBottomOfOriginalNodeAndEndOfPage);

        return $node;
    }

    private function addToSubjectOfSplitting(Node $node)
    {
        $this->getSubjectOfSplitting()->getCurrentPage()->add($node);
    }

    private function breakSubjectOfSplittingIncraseTranslation(Node $node, $nodeYCoordStart, $gapBeetwenBottomOfOriginalNodeAndEndOfPage)
    {
        $translation = $this->node->getPage()->getHeight() + $this->node->getPage()->getMarginBottom() - $nodeYCoordStart;
        $verticalTranslation = $translation - $gapBeetwenBottomOfOriginalNodeAndEndOfPage;
        
        $this->getSubjectOfSplitting()->createNextPage();
        $this->totalVerticalTranslation += $verticalTranslation;
        
        $this->getSubjectOfSplitting()->getCurrentPage()->add($node);
        $node->translate(0, -$translation);
    }
    
    /**
     * @return Node
     */
    private function getSubjectOfSplitting()
    {
        return $this->node;
    }
    
    private function shouldBeSplited(Node $node, $pageYCoordEnd)
    {
        $yEnd = $node->getDiagonalPoint()->getY();

        return ($yEnd < $pageYCoordEnd);
    }

    private function getChildYCoordOfFirstPoint(Node $node)
    {
        $yCoordOfFirstPoint = $node->getFirstPoint()->getY();

        return $yCoordOfFirstPoint;
    }
}