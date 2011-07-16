<?php

namespace PHPPdf\Formatter;

use PHPPdf\Glyph\Glyph;
use PHPPdf\Document;
use PHPPdf\Glyph\Text;
use PHPPdf\Util\Point;

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
    	
    	$previousTextLineHeight = 0;
    	foreach($glyph->getChildren() as $textGlyph)
    	{
    	    $words = $textGlyph->getWords();
    	    $wordsSizes = $textGlyph->getWordsSizes();
    	               
    	    $wordsLines = array();
    	    $lineSizes = array();
    	    $lineWidth = 0;
    
    	    $currentWordLine = array();
    	    $currentWidthOfLine = 0;
    
    	    $numberOfWords = count($words);
    	    
    	    $first = true;
    	    
    	    foreach($words as $index => $word)
    	    {
    	        $wordSize = $wordsSizes[$index];
    	        $newLineWidth = $currentWidthOfLine + $wordSize;
    	        
    	        $isLastWord = $index == ($numberOfWords - 1);
    	        $endXCoord = $newLineWidth + $currentPoint->getX();
    	        $isEndOfLine = $endXCoord > $glyph->getDiagonalPoint()->getX();
    	        
    	        if($first)
    	        {
    	            if($isEndOfLine)
    	            {
    	                $currentPoint = Point::getInstance($glyph->getFirstPoint()->getX(), $currentPoint->getY() - $previousTextLineHeight);
    	            }
    	            $boundary = $textGlyph->getBoundary();
    	            $boundary->setNext($currentPoint);
    	            $isEndOfLine = false;
    	        }
    	        
    	        $first = false;
    	        
    	        if($isEndOfLine)
    	        {
    	            $currentPoint = Point::getInstance($glyph->getFirstPoint()->getX(), $currentPoint->getY() - $textGlyph->getAttribute('line-height'));
    	        }
    	        elseif($isLastWord)
    	        {
    	            $currentWidthOfLine = $newLineWidth;
    	            $currentWordLine[] = $word;
    	        }
    	        
    	        if($isEndOfLine || $isLastWord)
    	        {
    	            $textGlyph->addLineOfWords($currentWordLine, $currentWidthOfLine);
    	            $currentPoint = $currentPoint->translate($currentWidthOfLine, 0);
    	            $currentWidthOfLine = 0;
    	            $currentWordLine = array();
    	        }
    	        
    	        if($isLastWord && $isEndOfLine)
    	        {
    	            $textGlyph->addLineOfWords(array($word), $wordSize);
    	        }
    
    	        if(!$isEndOfLine && !$isLastWord)
    	        {
    	            $currentWidthOfLine = $newLineWidth;
    	            $currentWordLine[] = $word;
    	        }
    	    }
    	    
    	    $previousTextLineHeight = $textGlyph->getAttribute('line-height');
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
        list($x, $y) = $text->getFirstPoint()->toArray();
        list($parentX, $parentY) = $text->getParent()->getStartDrawingPoint();

        $lineSizes = $text->getLineSizes();
        $lineHeight = $text->getAttribute('line-height');

        $startX = $x;

        $currentX = $x;
        $currentY = $y;
        $boundary = $text->getBoundary();
        foreach($lineSizes as $rowNumber => $width)
        {
            $newX = $x + $width;
            $newY = $currentY - $lineHeight;
            if($currentX !== $newX)
            {
                $boundary->setNext($newX, $currentY);
            }

            $boundary->setNext($newX, $newY);
            $currentX = $newX;
            $currentY = $newY;
            $x = $parentX + $text->getMarginLeft();
        }

        $boundary->setNext($x, $currentY);
        $currentY = $currentY + (count($lineSizes) - 1)*$lineHeight;
        $boundary->setNext($x, $currentY);
        $boundary->setNext($startX, $currentY);

        $boundary->close();
    }
}