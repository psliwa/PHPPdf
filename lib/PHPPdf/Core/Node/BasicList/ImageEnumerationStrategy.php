<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node\BasicList;

use PHPPdf\Exception\LogicException;
use PHPPdf\Core\UnitConverter;
use PHPPdf\Core\Document;
use PHPPdf\Core\Node\BasicList;
use PHPPdf\Core\Engine\GraphicsContext;

/**
 * This enumeration strategy uses image as enumeration element 
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ImageEnumerationStrategy extends AbstractEnumerationStrategy
{
    private $imageWidth;
    private $imageHeight;

    protected function doDrawEnumeration(Document $document, BasicList $list, GraphicsContext $gc, $xCoord, $yCoord)
    {
        $image = $this->getImage($list);

        $gc->drawImage($image, $xCoord, $yCoord - $this->imageWidth, $xCoord + $this->imageHeight, $yCoord);
    }

    protected function getEnumerationElementTranslations(Document $document, BasicList $list)
    {
        $image = $this->getImage($list);
        $fontSize = $list->getRecurseAttribute('font-size');
        
        list($this->imageWidth, $this->imageHeight) = $this->getImageDimension($document, $image, $fontSize);
        
        $xTranslation = 0;
        
        if($list->getAttribute('list-position') === BasicList::LIST_POSITION_OUTSIDE)
        {
            $xTranslation = -$this->imageWidth;
        }
        
        return array($xTranslation, 0);    
    }
    
    protected function getImage(BasicList $list)
    {
        $image = $list->getImage();
        
        if(!$image)
        {
            throw new LogicException('Image enumeration type requires not empty attribute "image" of BasicList.');
        }
        
        return $image;
    }
    
    private function getImageDimension(UnitConverter $converter, $image, $fontSize)
    {
        if($this->imageWidth === null && $this->imageHeight === null)
        {
            $imageHeight = $image->getOriginalHeight();
            $imageWidth = $image->getOriginalWidth();
            if($imageWidth > $fontSize)
            {
                $imageHeight = $imageHeight * $fontSize/$imageWidth;
                $imageWidth = $fontSize;
            }
            
            if($imageHeight > $fontSize)
            {
                $imageWidth = $imageWidth * $fontSize/$imageHeight;
                $imageHeight = $fontSize;
            }
            
            $this->imageWidth = $imageWidth;
            $this->imageHeight = $imageHeight;
        }
        
        return array($this->imageWidth, $this->imageHeight);
    }

    public function getWidthOfTheBiggestPosibleEnumerationElement(Document $document, BasicList $list)
    {
        $image = $this->getImage($list);
        $fontSize = $list->getRecurseAttribute('font-size');
        list($width, $height) = $this->getImageDimension($document, $image, $fontSize);

        return $width;        
    }
}