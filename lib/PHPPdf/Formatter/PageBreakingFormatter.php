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
 * TODO: refactoring
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class PageBreakingFormatter extends BaseFormatter
{
    protected $node;
    protected $totalVerticalTranslation = 0;
    
    public function format(Node $node, Document $document)
    {
        $columnFormatter = new ColumnBreakingFormatter();

        $this->node = $node;
        $this->totalVerticalTranslation = 0;

        $children = $this->node->getChildren();
        foreach($this->node->getChildren() as $child)
        {
            $child->translate(0, -$this->totalVerticalTranslation);
            
            $columnFormatter->format($child, $document);   

            $verticalTranslation = $columnFormatter->getLastVerticalTranslation();
            
            $this->breakChildIfNecessary($child);
            
            $this->totalVerticalTranslation += -$verticalTranslation;
        }
        
        foreach($node->getPages() as $page)
        {
            $page->preFormat($document);
        }
        
        $this->node = null;
    }

    private function breakChildIfNecessary(Node $node)
    {
        $childHasBeenBroken = false;
        $childMayBeBroken = true;
        
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
            if($this->shouldBeBroken($node, $pageYCoordEnd))
            {
                $node = $this->breakChildAndGetProductOfBreaking($node);
                $childHasBeenBroken = true;
            }
            else
            {
                if(!$childHasBeenBroken)
                {
                    $this->addToSubjectOfBreaking($node);
                }

                $childMayBeBroken = false;
            }
            
            $pageYCoordEnd = $this->getPageYCoordEnd();
        }
        while($childMayBeBroken);
    }
    
    private function shouldParentBeAutomaticallyBroken(Node $node)
    {
        return $node->getAttribute('break');
    }
    
    private function getPageYCoordEnd()
    {
        return $this->node->getPage()->getDiagonalPoint()->getY();
    }
    
    private function breakChildAndGetProductOfBreaking(Node $node)
    {
        $originalHeight = $node->getFirstPoint()->getY() - $node->getDiagonalPoint()->getY();
        $nodeYCoordStart = $this->getChildYCoordOfFirstPoint($node);
        $end = $this->getPageYCoordEnd();
        $breakLine = $nodeYCoordStart - $end;
        $breakNode = $node->breakAt($breakLine);

        $gapBeetwenBottomOfOriginalNodeAndEndOfPage = 0;

        if($breakNode)
        {           
            $gapBeetwenBottomOfOriginalNodeAndEndOfPage = $node->getDiagonalPoint()->getY() - $end;

            $gap = $originalHeight - (($node->getFirstPoint()->getY() - $node->getDiagonalPoint()->getY()) + ($breakNode->getFirstPoint()->getY() - $breakNode->getDiagonalPoint()->getY()));
            $this->totalVerticalTranslation += $gap;

            $nodeYCoordStart = $breakNode->getFirstPoint()->getY();
            $this->addToSubjectOfBreaking($node);
            $node = $breakNode;
        }

        $this->breakSubjectOfBreakingAndIncraseTranslation($node, $nodeYCoordStart, $gapBeetwenBottomOfOriginalNodeAndEndOfPage);

        return $node;
    }

    private function addToSubjectOfBreaking(Node $node)
    {
        $this->getSubjectOfBreaking()->getCurrentPage()->add($node);
    }

    private function breakSubjectOfBreakingAndIncraseTranslation(Node $node, $nodeYCoordStart, $gapBeetwenBottomOfOriginalNodeAndEndOfPage)
    {
        $translation = $this->node->getPage()->getHeight() + $this->node->getPage()->getMarginBottom() - $nodeYCoordStart;
        $verticalTranslation = $translation - $gapBeetwenBottomOfOriginalNodeAndEndOfPage;
        
        $this->getSubjectOfBreaking()->createNextPage();
        $this->totalVerticalTranslation += $verticalTranslation;
        
        $this->getSubjectOfBreaking()->getCurrentPage()->add($node);
        $node->translate(0, -$translation);
    }
    
    /**
     * @return Node
     */
    private function getSubjectOfBreaking()
    {
        return $this->node;
    }
    
    private function shouldBeBroken(Node $node, $pageYCoordEnd)
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