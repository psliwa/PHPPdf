<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Formatter;

use PHPPdf\Core\Node\Paragraph\Line;
use PHPPdf\Core\Node\Paragraph\LinePart;
use PHPPdf\Core\Node\Node;
use PHPPdf\Core\Document;
use PHPPdf\Core\Node\Text;
use PHPPdf\Core\Point;

/**
 * TODO: refactoring
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ParagraphFormatter extends BaseFormatter
{
    public function format(Node $node, Document $document)
    {
        $this->designateLinesOfWords($node);        
        $this->setTextBoundaries($node->getChildren());           
    }
    
	private function designateLinesOfWords(Node $node)
	{
    	$currentPoint = $node->getFirstPoint();
    	
    	$partsOfLine = array();
    	$yTranslation = 0;
    	$line = new Line($node, 0, $yTranslation);
    	
    	foreach($node->getChildren() as $textNode)
    	{
    	    $words = $textNode->getWords();
    	    $wordsSizes = $textNode->getWordsSizes();
    
    	    $currentWordLine = array();
    	    $currentWidthOfLine = 0;
    
    	    $numberOfWords = count($words);
    	    
    	    $first = true;
    	    
    	    $lineWidth = 0;
    	    foreach($words as $index => $word)
    	    {
    	        $wordSize = $wordsSizes[$index];
    	        $newLineWidth = $currentWidthOfLine + $wordSize;
    	        
    	        $endXCoord = $newLineWidth + $currentPoint->getX();
    	        $maxLineXCoord = $this->getMaxXCoord($node);
    	        $isEndOfLine = $endXCoord > $maxLineXCoord;
    	        
    	        if($isEndOfLine)
    	        {
    	            if($currentWordLine)
    	            {
        	            $partOfLine = new LinePart($currentWordLine, $currentWidthOfLine, $currentPoint->getX() - $node->getFirstPoint()->getX(), $textNode);
    	                $partsOfLine[] = $partOfLine;
    	                
    	                $line->addParts($partsOfLine);
    	                $node->addLine($line);
    	                
    	                $yTranslation += $line->getHeight();
    	                $line = new Line($node, 0, $yTranslation);
    	                $partsOfLine = array();
        	            
        	            $currentWidthOfLine = 0;
        	            $currentWordLine = array();
    	            }
    	            else
    	            {
    	                $line->addParts($partsOfLine);
    	                $node->addLine($line);
    	                
    	                $yTranslation += $line->getHeight();
    	                $line = new Line($node, 0, $yTranslation);
    	                $partsOfLine = array();
    	            }

    	            $currentPoint = Point::getInstance($node->getFirstPoint()->getX(), 0);
    	        }

	            $currentWidthOfLine = $currentWidthOfLine + $wordSize;
	            $currentWordLine[] = $word;
    	    }
    	    
            if($currentWordLine)
            {
                $partOfLine = new LinePart($currentWordLine, $currentWidthOfLine, $currentPoint->getX() - $node->getFirstPoint()->getX(), $textNode);
                $partsOfLine[] = $partOfLine;
                
	            $currentPoint = $currentPoint->translate($currentWidthOfLine, 0);
            }
    	}
    	
    	if($partsOfLine)
    	{
    	    $yTranslation += $line->getHeight();
    	    $line = new Line($node, 0, $yTranslation);
            $line->addParts($partsOfLine);
            $node->addLine($line);
    	}
    }
    
    private function getMaxXCoord(Node $node)
    {
        for($parent=$node->getParent(); $parent && !$parent->getWidth(); $parent=$parent->getParent())
        {
        }
        
        if(!$node->getWidth() && $parent && $parent->getWidth())
        {
            $node = $parent;
        }

        return $node->getFirstPoint()->getX() + $node->getWidth() - $node->getPaddingRight();
    }
    
    private function setTextBoundaries(array $textNodes)
    {
        foreach($textNodes as $textNode)
        {
            $this->setTextBoundary($textNode);
        }
    }
    
    private function setTextBoundary(Text $text)
    {
        $lineParts = $text->getLineParts();
        
        $points = array();
        foreach($lineParts as $part)
        {
            $points[] = $part->getFirstPoint();
        }
        
        list($x, $y) = $points[0]->toArray();
        $text->getBoundary()->setNext($points[0]);
        list($parentX, $parentY) = $text->getParent()->getFirstPoint()->toArray();

        $startX = $x;

        $currentX = $x;
        $currentY = $y;
        $boundary = $text->getBoundary();
        $totalHeight = 0;
        
        foreach($lineParts as $rowNumber => $part)
        {
            $height = $part->getText()->getLineHeightRecursively();
            $totalHeight += $height;
            $width = $part->getWidth();

            $startPoint = $points[$rowNumber];
            $newX = $startPoint->getX() + $width;
            $newY = $currentY - $height;
            if($currentX !== $newX)
            {
                $boundary->setNext($newX, $currentY);
            }

            $boundary->setNext($newX, $newY);
            $currentX = $newX;
            $currentY = $newY;
            $x = $startPoint->getX();
        }

        $boundary->setNext($x, $currentY);
        $currentY = $currentY + $totalHeight;
        $boundary->setNext($x, $currentY);
        $boundary->setNext($startX, $currentY);

        $boundary->close();
        
        $text->setHeight($text->getFirstPoint()->getY() - $text->getDiagonalPoint()->getY());
    }
}