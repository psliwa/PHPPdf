<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Formatter;

use PHPPdf\Exception\InvalidArgumentException;
use PHPPdf\Core\Node\Page;
use PHPPdf\Core\Document;
use PHPPdf\Core\Node\Node;

/**
 * Elastic page formatter
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ElasticPageFormatter extends BaseFormatter
{
    public function format(Node $node, Document $document)
    {
        if(!$node instanceof Page)
        {
            throw new InvalidArgumentException('ElasticPageFormatter works only with PHPPdf\Core\Node\Page class.');
        }
        
        $lastChild = null;
        foreach($node->getChildren() as $child)
        {
            if(!$lastChild || ($lastChild->getDiagonalPoint()->getY() - $lastChild->getMarginBottom()) > ($child->getDiagonalPoint()->getY() - $child->getMarginBottom()))
            {
                $lastChild = $child;
            }
        }
        
        $lastNodeYCoord = $lastChild ? ($lastChild->getDiagonalPoint()->getY() - $lastChild->getMarginBottom()) : $node->getRealHeight();
        
        $height = $node->getRealHeight() - $lastNodeYCoord + $node->getMarginBottom();
        $translate = $node->getRealHeight() - $height;
        
        $node->setPageSize($node->getRealWidth(), $height);
        
        foreach($node->getChildren() as $child)
        {
            $child->translate(0, $translate);
        }
        
        $node->removeGraphicsContext();
    }
}