<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Formatter;

use PHPPdf\Core\Engine\Font;
use PHPPdf\Core\Formatter\BaseFormatter,
    PHPPdf\Core\Node as Nodes,
    PHPPdf\Core\Document,
    PHPPdf\Core\Formatter\Chain;

/**
 * Calculates text's real dimension
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class TextDimensionFormatter extends BaseFormatter
{
    public function format(Nodes\Node $node, Document $document)
    {
        $maxPossibleWordLength = self::getMaxPossibleWordLength($node);
        $wordCandidates = self::createWordCandidates($node->getText());

        $node->setText('');

        $wordsSizes = array();
        $words = array();
        
        $font = $node->getFont($document);
        $fontSize = $node->getFontSizeRecursively();
        $encoding = $node->getEncoding();
        
        foreach($wordCandidates as $wordCandidate)
        {
            self::processWordCandidate($wordCandidate, $maxPossibleWordLength, $font, $fontSize, $encoding, $words, $wordsSizes);
        }

        $node->setWordsSizes($words, $wordsSizes);
    }
    
    private static function createWordCandidates($text)
    {
        $wordCandidates = preg_split('/[ \t]+/', $text);
        
        for($i=0, $lastIndex = count($wordCandidates) - 1; $i < $lastIndex; $i++)
        {
            $wordCandidates[$i] .= ' ';
        }
        
        return $wordCandidates;
    }
    
    private static function processWordCandidate($wordCandidate, $maxPossibleWordWidth, Font $font, $fontSize, $encoding, array &$words, array &$wordSizes)
    {
        $wordCandidateWidth = $font->getWidthOfText($wordCandidate, $fontSize);
        
        if($wordCandidateWidth > $maxPossibleWordWidth)
        {
            self::buildWordsNoGreaterThanGivenWidth($wordCandidate, $maxPossibleWordWidth, $font, $fontSize, $encoding, $words, $wordSizes);
        }
        else
        {
            $words[] = $wordCandidate;
            $wordSizes[] = $wordCandidateWidth;
        }
    }
    
    private static function buildWordsNoGreaterThanGivenWidth($wordCandidate, $maxPossibleWordWidth, Font $font, $fontSize, $encoding, array &$words, array &$wordSizes)
    {
        $wordLength = mb_strlen($wordCandidate, $encoding);

        $buildingWord = '';
        $buildingWordWidth = 0;
        
        for($i=0; $i<$wordLength; $i++)
        {
            $char = mb_substr($wordCandidate, $i, 1, $encoding);
            $charSize = $font->getWidthOfText($char, $fontSize);
            
            $nextBuildingWordWidth = $buildingWordWidth + $charSize;
            
            if($nextBuildingWordWidth > $maxPossibleWordWidth)
            {
                $words[] = $buildingWord;
                $wordSizes[] = $buildingWordWidth;
                
                $buildingWord = $char;
                $buildingWordWidth = $charSize;
            }
            else
            {
                $buildingWord .= $char;
                $buildingWordWidth += $charSize;
            }
        }

        //remaning word
        $words[] = $buildingWord;
        $wordSizes[] = $buildingWordWidth;
    }
    
    private static function getMaxPossibleWordLength(Nodes\Node $node)
    {
    	for($currentNode=$node; ;$currentNode = $currentNode->getParent())
    	{
    	    if($currentNode->getWidth())
    	    {
    	        return $currentNode->getWidth();
    	    }
    	}
    	
    	return 0;
    }
}