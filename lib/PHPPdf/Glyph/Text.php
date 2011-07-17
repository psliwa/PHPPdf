<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph;

use PHPPdf\Glyph\Glyph,
    PHPPdf\Formatter\Formatter,
    PHPPdf\Document,
    PHPPdf\Util\Point,
    PHPPdf\Util\DrawingTask;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Text extends Glyph
{   
    private $text;
    private $wordsInRows = array();
    private $lineSizes = array();
    private $textTransformator = null;
    
    private $words = array();
    private $wordsSizes = array();
    private $pointsOfWordsLines = array();

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

    public function setLineSizes(array $lineSizes)
    {
        $this->lineSizes = $lineSizes;
    }

    public function getLineSizes()
    {
        return $this->lineSizes;
    }

    public function getWordsInRows()
    {
        return $this->wordsInRows;
    }

    public function setWordsInRows(array $wordsInRows)
    {
        $this->wordsInRows = $wordsInRows;
    }
    
    public function addLineOfWords(array $words, $widthOfLine, Point $point)
    {
        $this->wordsInRows[] = $words;
        $this->lineSizes[] = $widthOfLine;
        $this->pointsOfWordsLines[] = $point;
    }

    public function getMinWidth()
    {
        return \max($this->lineSizes);
    }

    public function setFontSize($size)
    {
        parent::setFontSize($size);

        if($this->getAttribute('line-height') === null)
        {
            $this->setAttribute('line-height', (int) ($size + $size*0.2));
        }

        return $this;
    }

    protected function doDraw(Document $document)
    {
        if($this->isEmptyText())
        {
            return;
        }
        
        $drawingTask = new DrawingTask(function(Text $glyph)
        {
            $graphicsContext = $glyph->getGraphicsContext();

            $graphicsContext->saveGS();

            $font = $glyph->getFont();
            $fontSize = $glyph->getRecurseAttribute('font-size');
            $lineHeight = $glyph->getAttribute('line-height');
            $color = $glyph->getRecurseAttribute('color');

            $graphicsContext->setFont($font, $fontSize);

            if($color)
            {
                $graphicsContext->setFillColor($color);
            }

            list($x, $y) = $glyph->getStartDrawingPoint();
            $x -= $glyph->getPaddingLeft();
            $rowHeight = $y - $fontSize;
            list($parentX, $parentY) = $glyph->getParent()->getStartDrawingPoint();
            $lineSizes = $glyph->getLineSizes();

            $textAlign = $glyph->getRecurseAttribute('text-align');
            foreach($glyph->getWordsInRows() as $rowNumber => $words)
            {
                $start = $glyph->getStartLineDrawingXDimension($textAlign, $lineSizes[$rowNumber]);
                $graphicsContext->drawText(implode('', $words), $start+$x, $rowHeight, $glyph->getPage()->getAttribute('encoding'));
                $rowHeight -=$lineHeight;
                $x = $parentX + $glyph->getMarginLeft();
            }

            $graphicsContext->restoreGS();
        }, array($this));

        $this->addDrawingTask($drawingTask);
    }
    
    private function isEmptyText()
    {
        return !$this->getWordsInRows();
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
        $lineHeight = $this->getAttribute('line-height');
        $lineSplit = (int) ($height / $lineHeight);

        $clone = null;
        if($lineSplit > 0)
        {
            $lineSizes = $this->getLineSizes();
            $wordsInRows = $this->wordsInRows;

            $wordsInRowsForClone = \array_splice($wordsInRows, $lineSplit);
            $lineSizesForClone = \array_splice($lineSizes, $lineSplit);

            $lineSizes = \array_splice($lineSizes, 0, $lineSplit);
            $wordsInRows = \array_splice($wordsInRows, 0, $lineSplit);

            $this->setLineSizes($lineSizes);
            $this->setWordsInRows($wordsInRows);

            $clone = $this->copy();

            $this->setAttribute('padding-bottom', 0);
            $this->setAttribute('margin-bottom', 0);

            $clone->setAttribute('padding-top', 0);
            $clone->setAttribute('margin-top', 0);

            $clone->setLineSizes(\array_values($lineSizesForClone));
            $clone->setWordsInRows(\array_values($wordsInRowsForClone));

            $startDrawingPoint = $this->getFirstPoint();
            $this->reorganize($startDrawingPoint);
            $endDrawingPoint = $this->getDiagonalPoint();
            $clone->reorganize($endDrawingPoint->translate(-$this->getWidth(), $height - count($wordsInRows)*$lineHeight));
        }

        return $clone;
    }

    public function reorganize(Point $leftTopCornerPoint)
    {
        $height = $this->getAttribute('line-height') * count($this->getLineSizes()) + $this->getAttribute('padding-top') + $this->getAttribute('padding-bottom');
        if($this->getDisplay() === self::DISPLAY_INLINE)
        {
            $width = \max($this->getLineSizes()) + $this->getAttribute('padding-left') + $this->getAttribute('padding-right');
        }
        else
        {
            $width = $this->getWidth();
        }

        $this->setWidth($width);
        $this->setHeight($height);

        $boundary = $this->getBoundary();
        $boundary->reset();
        $boundary->setNext($leftTopCornerPoint)
                 ->setNext($leftTopCornerPoint->translate($width, 0))
                 ->setNext($leftTopCornerPoint->translate($width, $height))
                 ->setNext($leftTopCornerPoint->translate(0, $height))
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
}