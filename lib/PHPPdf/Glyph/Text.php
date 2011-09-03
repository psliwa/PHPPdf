<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph;

use PHPPdf\Glyph\Paragraph\LinePart;

use PHPPdf\Glyph\Glyph,
    PHPPdf\Formatter\Formatter,
    PHPPdf\Document,
    PHPPdf\Util\Point,
    PHPPdf\Util\DrawingTask;

/**
 * Text glyph
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Text extends Glyph
{   
    private $text;
    private $textTransformator = null;
    
    private $words = array();
    private $wordsSizes = array();
    private $pointsOfWordsLines = array();
    
    protected $lineParts = array();

    public function __construct($text = '', array $attributes = array())
    {
        $this->setText($text);
        
        parent::__construct($attributes);
    }

    public function initialize()
    {
        parent::initialize();
        
        $this->setAttribute('display', self::DISPLAY_INLINE);
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

    protected function doDraw(Document $document)
    {
        if($this->isEmptyText())
        {
            return;
        }

        foreach($this->lineParts as $part)
        {
            foreach($part->getDrawingTasks($document) as $task)
            {
                $this->addDrawingTask($task);
            }
        }
    }
    
    private function isEmptyText()
    {
        return !$this->text;
    }

    public function getStartLineDrawingXDimension($align, $lineWidth)
    {
        $parent = $this->getParent();
        $width = $parent->getWidth();
        switch($align)
        {
            case self::ALIGN_LEFT:
                return $this->getAttribute('padding-left');
            case self::ALIGN_RIGHT:
                return ($width - $lineWidth - $parent->getAttribute('padding-right') - $parent->getAttribute('padding-left'));
            case self::ALIGN_CENTER:
                return ($width - $parent->getAttribute('padding-right') - $parent->getAttribute('padding-left') - $lineWidth)/2;
            default:
                throw new \InvalidArgumentException(sprintf('Unsupported align type "%s".', $align));
        }
    }

    protected function doSplit($height)
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

    public function reorganize(Point $leftTopCornerPoint)
    {
        $boundary = $this->getBoundary();
        $boundary->reset();
        $boundary->setNext($leftTopCornerPoint)
                 ->setNext($leftTopCornerPoint->translate($this->getWidth(), 0))
                 ->setNext($leftTopCornerPoint->translate($this->getWidth(), $this->getHeight()))
                 ->setNext($leftTopCornerPoint->translate(0, $this->getHeight()))
                 ->close();
    }

    public function add(Glyph $glyph)
    {
        if(!$glyph instanceof Text)
        {
            return;
        }

        $this->setText($this->getText().$glyph->getText());
    }
    
    public function setWordsSizes(array $words, array $sizes)
    {
        if(count($words) != count($sizes))
        {
            throw new \InvalidArgumentException(sprintf('Words and sizes of words arrays have to have the same length.'));
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
    
    protected function isAbleToExistsAboveCoord($yCoord)
    {
        $yCoord += $this->getAncestorWithFontSize()->getAttribute('line-height');
        return $this->getFirstPoint()->getY() > $yCoord;
    }
}