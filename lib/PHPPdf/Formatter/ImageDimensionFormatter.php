<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Formatter;

use PHPPdf\Util;

use PHPPdf\Node as Nodes,
    PHPPdf\Document;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ImageDimensionFormatter extends BaseFormatter
{
    public function format(Nodes\Node $node, Document $document)
    {
        if($this->isImageAndSizesArentSet($node))
        {
            $width = $node->getWidth();
            $height = $node->getHeight();
            $src = $node->getAttribute('src');

            $originalWidth = $src->getOriginalWidth();
            $originalHeight = $src->getOriginalHeight();
            $originalRatio = $originalWidth/$originalHeight;

            if(!$width && !$height)
            {
                list($width, $height) = $this->setDimensionsFromParent($node);
            }
            
            list($width, $height) = Util::calculateDependantSizes($width, $height, $originalRatio);
            
            $node->setWidth($width);
            $node->setHeight($height);
        }
    }

    private function isImageAndSizesArentSet(Nodes\Node $node)
    {
        return ($node instanceof Nodes\Image && (!$node->getWidth() || !$node->getHeight()));
    }

    private function setDimensionsFromParent(Nodes\Node $node)
    {
        $parent = $node->getParent();
        $src = $node->getAttribute('src');

        $width = $src->getOriginalWidth();
        $height = $src->getOriginalHeight();

        if($width > $parent->getWidth() || $height > $parent->getHeight())
        {
            if($parent->getWidth() > $parent->getHeight())
            {
                $height = $parent->getHeight();
                $width = null;
            }
            else
            {
                $width = $parent->getWidth();
                $height = null;
            }
        }

        return array($width, $height);
    }
}