<?php

namespace PHPPdf\Glyph\BasicList;

use PHPPdf\Glyph\BasicList,
    PHPPdf\Glyph\GraphicsContext;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ImageEnumerationStrategy extends AbstractEnumerationStrategy
{
    private $imageWidth;
    private $imageHeight;

    protected function doDrawEnumeration(BasicList $list, GraphicsContext $gc, $xCoord, $yCoord)
    {
        $image = $this->getImage($list);

        $gc->drawImage($image, $xCoord, $yCoord - $this->imageWidth, $xCoord + $this->imageHeight, $yCoord);
    }

    protected function getEnumerationElementTranslations(BasicList $list)
    {
        $image = $this->getImage($list);
        $fontSize = $list->getRecurseAttribute('font-size');
        
        list($this->imageWidth, $this->imageHeight) = $this->getImageDimension($image, $fontSize);
        
        $xTranslation = 0;
        
        if($list->getAttribute('position') === BasicList::POSITION_OUTSIDE)
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
            throw new \LogicException('Image enumeration type requires not empty attribute "image" of BasicList.');
        }
        
        return $image;
    }
    
    private function getImageDimension($image, $fontSize)
    {
        if($this->imageWidth === null && $this->imageHeight === null)
        {
            $imageHeight = $image->getPixelHeight();
            $imageWidth = $image->getPixelWidth();
            
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

    public function getWidthOfTheBiggestPosibleEnumerationElement(BasicList $list)
    {
        $image = $this->getImage($list);
        $fontSize = $list->getRecurseAttribute('font-size');
        list($width, $height) = $this->getImageDimension($image, $fontSize);

        return $width;        
    }
}