<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Formatter;

use PHPPdf\Core\Document,
    PHPPdf\Core\Node\Node;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class VerticalAlignFormatter extends BaseFormatter
{
    public function format(Node $node, Document $document)
    {
        $verticalAlign = $node->getRecurseAttribute('vertical-align');
        
        if($verticalAlign == Node::VERTICAL_ALIGN_TOP || $verticalAlign == null)
        {
            return;
        }
        
        $this->processVerticalAlign($node, $verticalAlign);
    }
    
    private function processVerticalAlign(Node $node, $verticalAlign)
    {
        $minYCoord = $this->getMinimumYCoordOfChildren($node);

        $translation = $this->getVerticalTranslation($node, $minYCoord, $verticalAlign);

        $this->verticalTranslateOfNodes($node->getChildren(), $translation);
    }
    
    private function sortChildren($node)
    {
        $children = $node->getChildren();
        
        usort($children, function($firstChild, $secondChild){
            if($firstChild->getDiagonalPoint()->getY() < $secondChild->getDiagonalPoint()->getY())
            {
                return 1;
            }
            
            if($firstChild->getDiagonalPoint()->getY() == $secondChild->getDiagonalPoint()->getY())
            {
                return 0;
            }
            
            return -1;
        });
        
        return $children;
    }
    
    private function getMinimumYCoordOfChildren(Node $node)
    {
        $minYCoord = $node->getFirstPoint()->getY() - $node->getPaddingTop();

        foreach($node->getChildren() as $child)
        {
            $minYCoord = min($minYCoord, $child->getDiagonalPoint()->getY());
        }
        
        return $minYCoord;
    }
    
    private function getVerticalTranslation(Node $node, $minYCoord, $verticalAlign)
    {
        $difference = $minYCoord - ($node->getDiagonalPoint()->getY() + $node->getPaddingBottom());
        
        if($verticalAlign == Node::VERTICAL_ALIGN_MIDDLE)
        {
            $difference /= 2;
        }
        
        return $difference;
    }
    
    private function verticalTranslateOfNodes(array $nodes, $verticalTranslation)
    {
        foreach($nodes as $node)
        {
            $node->translate(0, $verticalTranslation);
        }
    }
}