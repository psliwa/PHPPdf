<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph\Paragraph;

use PHPPdf\Glyph\Glyph;

use PHPPdf\Util\DrawingTask,
    PHPPdf\Document,
    PHPPdf\Util\Point,
    PHPPdf\Glyph\Drawable,
    PHPPdf\Glyph\Text;

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
    }
    
    public function setLine(Line $line)
    {
        $this->line = $line;
    }
    
    public function getDrawingTasks(Document $document)
    {
        return array(new DrawingTask(function(Text $text, $point, $words, $width, $document) {
            $gc = $text->getGraphicsContext();
            $gc->saveGS();
            $fontSize = $text->getFontSize();
            
            $gc->setFont($text->getFont($document), $fontSize);
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
            
            $rotationGlyph = $text->getAncestorWithRotation();
        
            if($rotationGlyph)
            {
                $middlePoint = $rotationGlyph->getMiddlePoint();
                $gc->rotate($middlePoint->getX(), $middlePoint->getY(), $rotationGlyph->getAttribute('rotate'));
            }
 
            $yCoord = $point->getY() - $fontSize;
            $gc->drawText($words, $point->getX(), $point->getY() - $fontSize, $text->getEncoding());
            
            $textDecoration = $text->getTextDecorationRecursively();
            
            $lineDecorationYTranslation = false;
            
            if($textDecoration == Glyph::TEXT_DECORATION_UNDERLINE)
            {
                $lineDecorationYTranslation = -1;
            }
            elseif($textDecoration == Glyph::TEXT_DECORATION_LINE_THROUGH)
            {
                $lineDecorationYTranslation = $fontSize / 3;
            }
            elseif($textDecoration == Glyph::TEXT_DECORATION_OVERLINE)
            {
                $lineDecorationYTranslation = $fontSize - 1;
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
        }, array($this->text, $this->getFirstPoint(), $this->words, $this->width, $document)));
    }
    
    public function getFirstPoint()
    {
        $yTranslation = $this->line->getHeight() - $this->text->getAttribute('line-height');
        return $this->line->getFirstPoint()->translate($this->xTranslation, $yTranslation);
    }
    
    public function getHeight()
    {
        return $this->text->getRecurseAttribute('line-height');
    }
    
    public function getText()
    {
        return $this->text;
    }
    
    public function setText(Text $text)
    {
        $this->text = $text;
        $text->addLinePart($this);
    }
    
    public function getWidth()
    {
        return $this->width;
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
}