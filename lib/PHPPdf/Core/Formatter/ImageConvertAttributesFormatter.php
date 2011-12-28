<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Formatter;

use PHPPdf\Core\Node\Image;
use PHPPdf\Core\Node\Node;
use PHPPdf\Core\Engine\Image as EngineImage;
use PHPPdf\Util;
use PHPPdf\Core\Document;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ImageConvertAttributesFormatter extends ConvertAttributesFormatter
{
    public function format(Node $node, Document $document)
    {
        $this->convertPercentageDimensions($node);
        $this->setSizesIfOneOfDimensionIsntSet($node, $document);
        $this->convertAutoMargins($node);
        $this->convertDegreesToRadians($node);
    }
    
    private function setSizesIfOneOfDimensionIsntSet(Node $node, Document $document)
    {
        if($this->isImageAndSizesArentSet($node))
        {
            $width = $node->getWidth();
            $height = $node->getHeight();
            $source = $node->createSource($document);

            $originalWidth = $source->getOriginalWidth();
            $originalHeight = $source->getOriginalHeight();
            $originalRatio = $originalHeight ? $originalWidth/$originalHeight : 0;

            if(!$width && !$height)
            {
                list($width, $height) = $this->setDimensionsFromParent($source, $node);
            }
            
            list($width, $height) = Util::calculateDependantSizes($width, $height, $originalRatio);
            
            $node->setWidth($width);
            $node->setHeight($height);
        }
    }

    private function isImageAndSizesArentSet(Node $node)
    {
        return ($node instanceof Image && (!$node->getWidth() || !$node->getHeight()));
    }

    private function setDimensionsFromParent(EngineImage $sourceImage, Node $node)
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