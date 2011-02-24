<?php

namespace PHPPdf\Glyph;

use PHPPdf\Glyph\AbstractGlyph,
    PHPPdf\Formatter\Formatter,
    PHPPdf\Document,
    PHPPdf\Util\Point,
    PHPPdf\Util\DrawingTask;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Text extends AbstractGlyph
{
    private $text;
    private $wordsInRows = array();
    private $lineSizes = array();

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

    public function setText($text)
    {
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
        $drawingTask = new DrawingTask(function(Text $glyph)
        {
            $page = $glyph->getPage();
            $graphicsContext = $page->getGraphicsContext();

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
                $graphicsContext->drawText(implode(' ', $words), $start+$x, $rowHeight, $page->getAttribute('encoding'));
                $rowHeight -=$lineHeight;
                $x = $parentX + $glyph->getMarginLeft();
            }

            $graphicsContext->restoreGS();
        }, array($this));

        $this->addDrawingTask($drawingTask);
    }

    public function getStartLineDrawingXDimension($align, $lineWidth)
    {
        $parent = $this->getParent();
        $width = $parent->getWidth();
        switch($align)
        {
            case self::ALIGN_LEFT:
                return $this->getPaddingLeft();
            case self::ALIGN_RIGHT:
                return ($parent->getWidth() - $lineWidth - $parent->getPaddingRight() - $parent->getPaddingLeft());
            case self::ALIGN_CENTER:
                return ($parent->getWidth() - $parent->getPaddingRight() - $parent->getPaddingLeft() - $lineWidth)/2;
            default:
                throw new \InvalidArgumentException(sprintf('Unsupported align type "%s".', $align));
        }
    }

    protected function doSplit($height)
    {
        $lineHeight = $this->getLineHeight();
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

            $this->setPaddingBottom(0);
            $this->setMarginBottom(0);

            $clone->setPaddingTop(0);
            $clone->setMarginTop(0);

            $clone->setLineSizes(\array_values($lineSizesForClone));
            $clone->setWordsInRows(\array_values($wordsInRowsForClone));

            $startDrawingPoint = $this->getBoundary()->getFirstPoint();
            $this->reorganize($startDrawingPoint);
            $endDrawingPoint = $this->getBoundary()->getDiagonalPoint();
            $clone->reorganize($endDrawingPoint->translate(-$this->getWidth(), $height - count($wordsInRows)*$lineHeight));
        }

        return $clone;
    }

    public function reorganize(Point $leftTopCornerPoint)
    {
        $height = $this->getLineHeight() * count($this->getLineSizes()) + $this->getPaddingTop() + $this->getPaddingBottom();
        if($this->getDisplay() === self::DISPLAY_INLINE)
        {
            $width = \max($this->getLineSizes()) + $this->getPaddingLeft() + $this->getPaddingRight();
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
}