<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Node;

use PHPPdf\Document,
    PHPPdf\Node\Container,
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

    public function breakAt($height)
    {
        throw new \LogicException('PageCollection can\'t be broken.');
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