<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph;

use PHPPdf\Document,
    PHPPdf\Glyph\Container,
    PHPPdf\Formatter\Formatter;

/**
 * Collection of the pages
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class PageCollection extends Container
{
    public function getAttribute($name)
    {
        return null;
    }

    public function setAttribute($name, $value)
    {
        return $this;
    }

    public function split($height)
    {
        throw new \LogicException('PageCollection can\'t be splitted.');
    }

    public function format(Document $document)
    {
        foreach($this->getChildren() as $page)
        {
            $page->preFormat($document);
            $page->format($document);
        }
    }
    
    public function getGraphicsContext()
    {
        return null;
    }
}