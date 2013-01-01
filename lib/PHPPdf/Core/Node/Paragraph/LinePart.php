<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node\Paragraph;

use PHPPdf\Core\DrawingTaskHeap;
use PHPPdf\Core\Node\Node,
    PHPPdf\Core\DrawingTask,
    PHPPdf\Core\Document,
    PHPPdf\Core\Point,
    PHPPdf\Core\Node\Drawable,
    PHPPdf\Core\Node\Text;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class LinePart implements Drawable
{
    private $text;
    private $words;
    private $line;
    private $xTranslation;
    private $yTranslation;
    private $width;
    private $wordSpacing = null;
    private $numberOfWords = null;
    
    public function __construct($words, $width, $xTranslation, Text $text)
    {
        $this->setWords($words);
        $this->width = $width;
        $text->addLinePart($this);
        $this->text = $text;
        $this->xTranslation = $xTranslation;
    }
    
    public function setWords($words)
    {
        if(is_array($words))
        {
            $words = implode('', $words);
        }
        
        $this->words = (string) $words;
        $this->numberOfWords = null;
    }
    
    public function getNumberOfWords()
    {
        if($this->numberOfWords === null)
        {
            $this->numberOfWords = count(explode(' ', rtrim($this->words)));
        }
        
        return $this->numberOfWords;
    }
    
    public function setLine(Line $line)
    {
        $this->line = $line;
    }
    
    /**
     * @param float Word spacing in units
     */
    public function setWordSpacing($wordSpacing)
    {
        $this->wordSpacing = $wordSpacing;
    }
    
    public function collectOrderedDrawingTasks(Document $document, DrawingTaskHeap $tasks)
    {
        $tasks->insert(new DrawingTask(function(Text $text, $point, $words, $width, $document, $linePartWordSpacing, Point $translation) {
            $gc = $text->getGraphicsContext();
            $gc->saveGS();
            $fontSize = $text->getFontSizeRecursively();
            
            $font = $text->getFont($document);
            $gc->setFont($font, $fontSize);
            $color = $text->getRecurseAttribute('color');
            
            if($color)
            {
                $gc->setFillColor($color);
            }
            
            $alpha = $text->getAlpha();
            
            if($alpha !== null)
            {
                $gc->setAlpha($alpha);
            }
            
            $rotationNode = $text->getAncestorWithRotation();
        
            if($rotationNode)
            {
                $middlePoint = $rotationNode->getMiddlePoint();
                $gc->rotate($middlePoint->getX(), $middlePoint->getY(), $rotationNode->getRotate());
            }
 
            if(!$translation->isZero())
            {
                $point = $point->translate($translation->getX(), $translation->getY());
            }
            
            $yCoord = $point->getY() - $fontSize;
            $wordSpacing = 0;
            
            if($linePartWordSpacing !== null)
            {
                $wordSpacing = $linePartWordSpacing;
            }
            $gc->drawText($words, $point->getX(), $point->getY() - $fontSize, $text->getEncoding(), $wordSpacing);
            
            $textDecoration = $text->getTextDecorationRecursively();        
            
            switch($textDecoration)
            {
                case Node::TEXT_DECORATION_NONE:
                    $lineDecorationYTranslation = false;
                    break;
                case Node::TEXT_DECORATION_UNDERLINE;
                    $lineDecorationYTranslation = -1;
                    break;
                case Node::TEXT_DECORATION_LINE_THROUGH:
                    $lineDecorationYTranslation = $fontSize / 3;
                    break;
                case Node::TEXT_DECORATION_OVERLINE;
                    $lineDecorationYTranslation = $fontSize - 1;
                    break;
                default:
                    //FIXME: throw exception?
                    $lineDecorationYTranslation = false;
                    break;
            }

            if($lineDecorationYTranslation !== false)
            {
                $gc->setLineWidth(0.5);
                if($color)
                {
                    $gc->setLineColor($color);
                }
                
                $yCoord = $yCoord + $lineDecorationYTranslation;
                $gc->drawLine($point->getX(), $yCoord, $point->getX() + $width, $yCoord);
            }

            $gc->restoreGS();
        }, array($this->text, $this->getFirstPoint(), $this->words, $this->width, $document, $this->wordSpacing, $this->text->getPositionTranslation())));
    }
    
    public function collectUnorderedDrawingTasks(Document $document, DrawingTaskHeap $tasks)
    {
    }
    
    public function collectPostDrawingTasks(Document $document, DrawingTaskHeap $tasks)
    {
    }
    
    public function getFirstPoint()
    {
        $yTranslation = $this->line->getHeight() - $this->text->getLineHeightRecursively();
        return $this->line->getFirstPoint()->translate($this->xTranslation, $yTranslation);
    }
    
    public function getHeight()
    {
        return $this->text->getLineHeightRecursively();
    }
    
    public function getText()
    {
        return $this->text;
    }
    
    public function setText(Text $text)
    {
        if($this->text !== $text)
        {
            $oldText = $this->text;
            
            if($oldText)
            {
                $oldText->removeLinePart($this);
            }
            
            $this->text = $text;
            $text->addLinePart($this);
        }
    }
    
    public function getWidth()
    {
        $width = $this->width;
        if($this->wordSpacing !== null)
        {
            $width += $this->getWordSpacingSum();
        }

        return $width;
    }
    
    public function getWordSpacingSum()
    {
        if($this->wordSpacing === null)
        {
            return 0;
        }
        
        return ($this->getNumberOfWords() - 1)*$this->wordSpacing;
    }
    
    public function getLineHeight()
    {
        return $this->line->getHeight();
    }
    
    public function horizontalTranslate($translate)
    {
        $this->xTranslation += $translate;
    }
    
    public function verticalTranslate($translate)
    {
        $this->yTranslation += $translate;
    }
    
    public function getXTranslation()
    {
        return $this->xTranslation;
    }
    
    public function flush()
    {
        $this->text = array();
        $this->line = null;
    }
}