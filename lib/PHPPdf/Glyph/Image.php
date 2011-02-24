<?php

namespace PHPPdf\Glyph;

use PHPPdf\Document,
    PHPPdf\Util\DrawingTask,
    PHPPdf\Glyph\AbstractGlyph;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Image extends AbstractGlyph
{
    public function initialize()
    {
        parent::initialize();
        $this->addAttribute('src');
    }

    protected function doDraw(Document $document)
    {
        $callback = function($glyph)
        {
            $page = $glyph->getPage();
            $graphicsContext = $page->getGraphicsContext();

            list($x, $y) = $glyph->getStartDrawingPoint();
            $image = $glyph->getAttribute('src');
            $graphicsContext->drawImage($image, $x, $y-$glyph->getHeight(), $x+$glyph->getWidth(), $y);
        };
        
        $drawingTask = new DrawingTask($callback, array($this));

        $this->addDrawingTask($drawingTask);
    }

    public function preFormat(Document $document)
    {
        $src = $this->getAttribute('src');

        if(is_string($src))
        {
            $src = \Zend_Pdf_Image::imageWithPath($src);
            $this->setAttribute('src', $src);
        }
        
        if(!$this->getWidth() && !$this->getHeight())
        {

            $this->setWidth($src->getPixelWidth());
            $this->setHeight($src->getPixelHeight());
        }
    }

    public function split($height)
    {
        return null;
    }
}