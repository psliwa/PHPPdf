<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Formatter;

use PHPPdf\Engine\Image;

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
            $source = $document->createImage($src);

            $originalWidth = $source->getOriginalWidth();
            $originalHeight = $source->getOriginalHeight();
            $originalRatio = $originalWidth/$originalHeight;

            if(!$width && !$height)
            {
                list($width, $height) = $this->setDimensionsFromParent($source, $node);
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

    private function setDimensionsFromParent(Image $sourceImage, Nodes\Node $node)
    {
        $parent = $node->getParent();

        $width = $sourceImage->getOriginalWidth();
        $height = $sourceImage->getOriginalHeight();

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