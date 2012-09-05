<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node;

use PHPPdf\Exception\InvalidArgumentException;
use PHPPdf\Core\DrawingTaskHeap;
use PHPPdf\Core\Node\Node;
use PHPPdf\Core\UnitConverter;
use PHPPdf\Core\Node\Paragraph\LinePart;
use PHPPdf\Core\Formatter\Formatter;
use PHPPdf\Core\Document;
use PHPPdf\Core\Point;
use PHPPdf\Core\DrawingTask;

/**
 * Text node
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Text extends Node
{   
    private $text;
    private $textTransformator = null;
    
    private $words = array();
    private $wordsSizes = array();
    private $pointsOfWordsLines = array();
    
    protected $lineParts = array();
    
    private $childTexts = array();

    public function __construct($text = '', array $attributes = array(), UnitConverter $converter = null)
    {
        $this->setText($text);
        
        parent::__construct($attributes, $converter);
    }

    public function initialize()
    {
        parent::initialize();
        
        $this->setAttribute('text-align', null);
    }
    
    public function setTextTransformator(TextTransformator $transformator)
    {
        $this->textTransformator = $transformator;
    }

    public function setText($text)
    {
        if($this->textTransformator !== null)
        {
            $text = $this->textTransformator->transform($text);
        }
        
        $this->text = (string) $text;
    }
    
    protected function beforeFormat(Document $document)
    {
        foreach($this->childTexts as $text)
        {
            $text->beforeFormat($document);
            
            $this->text .= $text->getText();
        }
        
        $this->childTexts = array();
    }

    public function getText()
    {
        return $this->text;
    }
    
    public function addLineOfWords(array $words, $widthOfLine, Point $point)
    {
        $this->wordsInRows[] = $words;
        $this->lineSizes[] = $widthOfLine;
        $this->pointsOfWordsLines[] = $point;
    }

    public function getMinWidth()
    {
        $minWidth = 0;
        foreach($this->lineParts as $part)
        {
            $minWidth = max($minWidth, $part->getWidth());
        }
        return $minWidth;
    }

    protected function doDraw(Document $document, DrawingTaskHeap $tasks)
    {
        foreach($this->lineParts as $part)
        {
            $part->collectOrderedDrawingTasks($document, $tasks);
        }
    }

    protected function doBreakAt($height)
    {
        $clone = null;
        if($height > 0)
        {
            $clone = $this->copy();

            $this->setAttribute('padding-bottom', 0);
            $this->setAttribute('margin-bottom', 0);

            $clone->setAttribute('padding-top', 0);
            $clone->setAttribute('margin-top', 0);

            $startDrawingPoint = $this->getFirstPoint();
            $oldHeight = $this->getHeight();
            $this->setHeight($height);
            $this->reorganize($startDrawingPoint);
            $endDrawingPoint = $this->getDiagonalPoint();
            $clone->setHeight($oldHeight - $height);
            $clone->reorganize($endDrawingPoint->translate(-$this->getWidth(), 0));
        }

        return $clone;
    }

    private function reorganize(Point $leftTopCornerPoint)
    {
        $boundary = $this->getBoundary();
        $boundary->reset();
        $boundary->setNext($leftTopCornerPoint)
                 ->setNext($leftTopCornerPoint->translate($this->getWidth(), 0))
                 ->setNext($leftTopCornerPoint->translate($this->getWidth(), $this->getHeight()))
                 ->setNext($leftTopCornerPoint->translate(0, $this->getHeight()))
                 ->close();
    }

    public function add(Node $node)
    {
        if(!$node instanceof Text)
        {
            return;
        }
        $this->childTexts[] = $node;
    }
    
    public function setWordsSizes(array $words, array $sizes)
    {
        if(count($words) != count($sizes))
        {
            throw new InvalidArgumentException(sprintf('Words and sizes of words arrays have to have the same length.'));
        }

        $this->words = $words;
        $this->wordsSizes = $sizes;
    }
    
    public function getWords()
    {
        return $this->words;
    }
    
    public function getWordsSizes()
    {
        return $this->wordsSizes;
    }
    
    public function getPointsOfWordsLines()
    {
        return $this->pointsOfWordsLines;
    }
    
    public function translate($x, $y)
    {
        parent::translate($x, $y);
        
        foreach($this->pointsOfWordsLines as $i => $point)
        {
            $this->pointsOfWordsLines[$i] = $point->translate($x, $y);
        }
    }
    
    public function addLinePart(LinePart $linePart)
    {
        $this->lineParts[] = $linePart;
    }
    
    public function getLineParts()
    {
        return $this->lineParts;
    }
    
    public function removeLinePart(LinePart $linePart)
    {
        $key = array_search($linePart, $this->lineParts, true);
        
        if($key !== false)
        {
            unset($this->lineParts[$key]);
        }
    }
    
    protected function setDataFromUnserialize(array $data)
    {
        parent::setDataFromUnserialize($data);
        
        $this->text = $data['text'];
    }
    
    protected function getDataForSerialize()
    {
        $data = parent::getDataForSerialize();
        
        $data['text'] = $this->text;
        
        return $data;
    }
    
    public function copy()
    {
        $copy = parent::copy();

        $copy->lineParts = array();

        return $copy;
    }
    
    public function isLeaf()
    {
        return true;
    }
    
    public function isInline()
    {
        return true;
    }

    protected function isAbleToExistsAboveCoord($yCoord)
    {
        $yCoord += $this->getAncestorWithFontSize()->getAttribute('line-height');
        return $this->getFirstPoint()->getY() > $yCoord;
    }
    
    public function flush()
    {
        $this->lineParts = array();

        parent::flush();
    }
}