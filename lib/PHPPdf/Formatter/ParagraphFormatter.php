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
    
    	    $currentWordLine = array();
    	    $currentWidthOfLine = 0;
    
    	    $numberOfWords = count($words);
    	    
    	    $first = true;
    	    
    	    $lineWidth = 0;
    	    foreach($words as $index => $word)
    	    {
    	        $wordSize = $wordsSizes[$index];
    	        $newLineWidth = $currentWidthOfLine + $wordSize;
    	        
    	        $isLastWord = $index == ($numberOfWords - 1);
    	        $endXCoord = $newLineWidth + $currentPoint->getX();
    	        $maxLineXCoord = $glyph->getFirstPoint()->getX() + $glyph->getWidth();
    	        $isEndOfLine = $endXCoord > $maxLineXCoord;
    	        
    	        if($isEndOfLine)
    	        {
    	            if($currentWordLine)
    	            {
        	            $startPoint = $this->getStartPoint($glyph->getRecurseAttribute('text-align'), $currentWidthOfLine, $maxLineXCoord, $currentPoint);
    	                $textGlyph->addLineOfWords($currentWordLine, $currentWidthOfLine, $startPoint);
        	            $currentWidthOfLine = 0;
        	            $currentWordLine = array();
    	            }
    	            //jeśli jest to pierwsze słowo to przesun o lineHeight poprzedniego textGlyph
    	            $lineHeight = $index == 0 ? $previousTextLineHeight : $textGlyph->getAttribute('line-height');
    	            $currentPoint = Point::getInstance($glyph->getFirstPoint()->getX(), $currentPoint->getY() - $lineHeight);
    	        }

	            $currentWidthOfLine = $currentWidthOfLine + $wordSize;
	            $currentWordLine[] = $word;
    	    }
    	    
            if($currentWordLine)
            {
                $startPoint = $this->getStartPoint($glyph->getRecurseAttribute('text-align'), $currentWidthOfLine, $maxLineXCoord, $currentPoint);
	            $textGlyph->addLineOfWords($currentWordLine, $currentWidthOfLine, $startPoint);
	            $currentPoint = $currentPoint->translate($currentWidthOfLine, 0);
            }
    	    
    	    $previousTextLineHeight = $textGlyph->getAttribute('line-height');
    	}
    }
    
    private function getStartPoint($align, $widthOfWordsLine, $maxAllowedXCoordOfLine, Point $firstPoint)
    {
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
        $points = $text->getPointsOfWordsLines();
        list($x, $y) = $points[0]->toArray();
        $text->getBoundary()->setNext($points[0]);
        list($parentX, $parentY) = $text->getParent()->getStartDrawingPoint();

        $lineSizes = $text->getLineSizes();
        $lineHeight = $text->getAttribute('line-height');

        $startX = $x;

        $currentX = $x;
        $currentY = $y;
        $boundary = $text->getBoundary();
        foreach($lineSizes as $rowNumber => $width)
        {
            $startPoint = $points[$rowNumber];
            $newX = $startPoint->getX() + $width;
            $newY = $currentY - $lineHeight;
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
        $currentY = $currentY + (count($lineSizes) - 1)*$lineHeight;
        $boundary->setNext($x, $currentY);
        $boundary->setNext($startX, $currentY);

        $boundary->close();
    }
}