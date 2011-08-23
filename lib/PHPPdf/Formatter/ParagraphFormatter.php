<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Formatter;

use PHPPdf\Glyph\Paragraph\Line;
use PHPPdf\Glyph\Paragraph\LinePart;
use PHPPdf\Glyph\Glyph;
use PHPPdf\Document;
use PHPPdf\Glyph\Text;
use PHPPdf\Util\Point;

/**
 * TODO: refactoring
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ParagraphFormatter extends BaseFormatter
{
    public function format(Glyph $glyph, Document $document)
    {
        $this->designateLinesOfWords($glyph);        
        $this->setTextBoundaries($glyph->getChildren());           
    }
    
	private function designateLinesOfWords($glyph)
	{
    	$currentPoint = $glyph->getFirstPoint();
    	
    	$partsOfLine = array();
    	$yTranslation = 0;
    	$line = new Line($glyph, 0, $yTranslation);
    	
    	foreach($glyph->getChildren() as $textGlyph)
    	{
    	    $words = $textGlyph->getWords();
    	    $wordsSizes = $textGlyph->getWordsSizes();    	               
    
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
    	        $maxLineXCoord = $this->getMaxXCoord($glyph);
    	        $isEndOfLine = $endXCoord > $maxLineXCoord;
    	        
    	        if($isEndOfLine)
    	        {
    	            if($currentWordLine)
    	            {
        	            $partOfLine = new LinePart($currentWordLine, $currentWidthOfLine, $currentPoint->getX() - $glyph->getFirstPoint()->getX(), $textGlyph);
    	                $partsOfLine[] = $partOfLine;
    	                
    	                $line->addParts($partsOfLine);
    	                $glyph->addLine($line);
    	                
    	                $yTranslation += $line->getHeight();
    	                $line = new Line($glyph, 0, $yTranslation);
    	                $partsOfLine = array();
        	            
        	            $currentWidthOfLine = 0;
        	            $currentWordLine = array();
    	            }
    	            else
    	            {
    	                $line->addParts($partsOfLine);
    	                $glyph->addLine($line);
    	                
    	                $yTranslation += $line->getHeight();
    	                $line = new Line($glyph, 0, $yTranslation);
    	                $partsOfLine = array();
    	            }

    	            $currentPoint = Point::getInstance($glyph->getFirstPoint()->getX(), 0);
    	        }

	            $currentWidthOfLine = $currentWidthOfLine + $wordSize;
	            $currentWordLine[] = $word;
    	    }
    	    
            if($currentWordLine)
            {
                $partOfLine = new LinePart($currentWordLine, $currentWidthOfLine, $currentPoint->getX() - $glyph->getFirstPoint()->getX(), $textGlyph);
                $partsOfLine[] = $partOfLine;
                
	            $currentPoint = $currentPoint->translate($currentWidthOfLine, 0);
            }
    	}
    	
    	if($partsOfLine)
    	{
    	    $yTranslation += $line->getHeight();
    	    $line = new Line($glyph, 0, $yTranslation);
            $line->addParts($partsOfLine);
            $glyph->addLine($line);
    	}
    }
    
    private function getMaxXCoord(Glyph $glyph)
    {
        for($parent=$glyph->getParent(); $parent && !$parent->getWidth(); $parent=$parent->getParent())
        {
        }
        
        if(!$glyph->getWidth() && $parent && $parent->getWidth())
        {
            $glyph = $parent;
        }

        return $glyph->getFirstPoint()->getX() + $glyph->getWidth();
    }
    
    private function getStartPoint($align, $widthOfWordsLine, $maxAllowedXCoordOfLine, Point $firstPoint)
    {
        return $firstPoint;
        switch($align)
        {
            case Glyph::ALIGN_LEFT:
                return $firstPoint;
            case Glyph::ALIGN_RIGHT:
                return $firstPoint->translate(($maxAllowedXCoordOfLine - $firstPoint->getX()) - $widthOfWordsLine, 0);
            case Glyph::ALIGN_CENTER:
                return $firstPoint->translate((($maxAllowedXCoordOfLine - $firstPoint->getX()) - $widthOfWordsLine)/2, 0);
            default:
                throw new \InvalidArgumentException(sprintf('Unsupported align type "%s".', $align));
        }
    }
    
    private function setTextBoundaries(array $textGlyphs)
    {
        foreach($textGlyphs as $textGlyph)
        {
            $this->setTextBoundary($textGlyph);
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
            $height = $part->getText()->getRecurseAttribute('line-height');
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