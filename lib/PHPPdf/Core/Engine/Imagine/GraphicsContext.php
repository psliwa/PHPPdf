<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Engine\Imagine;

use Imagine\Image\Point;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use PHPPdf\Core\Engine\AbstractGraphicsContext;
use PHPPdf\Core\Engine\GraphicsContext as BaseGraphicsContext;
use PHPPdf\Core\Engine\Image as BaseImage;
use PHPPdf\Core\Engine\Font as BaseFont;
use Imagine\Image\Color as ImagineColor;

/**
 * Graphics context for Imagine
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class GraphicsContext extends AbstractGraphicsContext
{
    private static $originalState = array(
        'fillColor' => null,
        'lineColor' => null,
        'lineWidth' => null,
        'lineDashingPattern' => null,
        'alpha' => 1,
        'font' => null,
        'fontSize' => null,
    );
    
    private $stateStack = array();
    
    private $state = array();
    
    private $image;
    private $imagine;
    
    public function __construct(ImagineInterface $imagine, $imageOrSize)
    {
        $this->state = self::$originalState;
        
        $this->imagine = $imagine;

        if($imageOrSize instanceof ImageInterface)
        {
            $this->image = $imageOrSize;
        }
        else
        {
            list($width, $height) = explode(':', $imageOrSize);
            $this->image = $imagine->create(new Box($width, $height));
        }
    }
    
    protected function doClipRectangle($x1, $y1, $x2, $y2)
    {
        
    }
    
    protected function doSaveGS()
    {
        array_unshift($this->stateStack, $this->state);
    }
    
    protected function doRestoreGS()
    {
        $state = array_shift($this->stateStack);
        
        if($state === null)
        {
            $state = self::$originalState;
        }
        
        $this->state = $state;
    }
    
    protected function doDrawImage(BaseImage $image, $x1, $y1, $x2, $y2)
    {
        $height = $y2 - $y1;
        $width = $x2 - $x1;
        
        $imagineImage = $image->getWrappedImage();
        
        $box = $imagineImage->getSize();
        $drawedImageHeight = $box->getHeight();
        $drawedImageWidth = $box->getWidth();
        
        if($height != $drawedImageHeight || $width != $drawedImageWidth)
        {
            $newBox = new Box($width, $height);
            $imagineImage->resize($newBox);
        }
        
        $y = $this->getHeight() - ($y1 + $height);
        $this->image->paste($imagineImage, new Point($x1, $y));
    }
    
    protected function doDrawLine($x1, $y1, $x2, $y2)
    {
        //TODO: throw exception if lineColor is not set
        //TODO: line width
        $color = new ImagineColor($this->state['lineColor']);
        
        $height = $this->getHeight();
        
        $this->image->draw()->line(new Point($x1, $height - $y1), new Point($x2, $height - $y2), $color);
    }
    
    protected function doSetFillColor($colorData)
    {
        $this->state['fillColor'] = $colorData;
    }
    
    protected function doSetLineColor($colorData)
    {
        $this->state['lineColor'] = $colorData;
    }
    
    protected function doDrawPolygon(array $x, array $y, $type)
    {
        $color = new ImagineColor($this->state['fillColor']);
        
        $fill = $type > 0;
        
        $coords = array();
        
        foreach($x as $i => $coord)
        {
            $coords[] = new Point($coord, $this->getHeight() - $y[$i]);
        }
        
        $polygons = array();
        
        if(in_array($type, array(self::SHAPE_DRAW_FILL, self::SHAPE_DRAW_FILL_AND_STROKE)))
        {
            $polygons[] = array(new ImagineColor($this->state['fillColor']), true);
        }
        
        if(in_array($type, array(self::SHAPE_DRAW_FILL_AND_STROKE, self::SHAPE_DRAW_STROKE)))
        {
            $polygons[] = array(new ImagineColor($this->state['lineColor']), false);
        }

        foreach($polygons as $polygon)
        {
            list($color, $fill) = $polygon;
            $this->image->draw()->polygon($coords, $color, $fill);
        }
    }
    
    protected function doDrawText($text, $x, $y, $encoding, $wordSpacing = 0, $fillType = self::SHAPE_DRAW_FILL)
    {
        $font = $this->state['font'];
        $color = $this->state['lineColor'];
        $size = $this->state['fontSize'];
        
        $imagineFont = $font->getWrappedFont($color, $size);
        
        $position = new Point($x, $this->getHeight() - $y - $size);
        
        $this->image->draw()->text($text, $imagineFont, $position);
    }
    
    protected function doDrawRoundedRectangle($x1, $y1, $x2, $y2, $radius, $fillType = self::SHAPE_DRAW_FILL_AND_STROKE)
    {
        
    }
    
    protected function doSetLineWidth($width)
    {
        $this->state['lineWidth'] = $width;
    }
    
    protected function doSetLineDashingPattern($pattern)
    {
        $this->state['lineDashingPattern'] = $pattern;
    }
    
    protected function doUriAction($x1, $y1, $x2, $y2, $uri)
    {
        //not supported
    }
    
    protected function doGoToAction(BaseGraphicsContext $gc, $x1, $y1, $x2, $y2, $top)
    {
        //not supported
    }
    
    protected function doAttachStickyNote($x1, $y1, $x2, $y2, $text)
    {
        //not supported
    }
    
    protected function doSetAlpha($alpha)
    {
        $this->state['alpha'] = $alpha;
    }
    
    //TODO: obsługa rotate przy saveGS i restoreGS
    protected function doRotate($x, $y, $angle)
    {
        
    }
    
    public function getWidth()
    {
        return $this->image->getSize()->getWidth();
    }
    
    public function getHeight()
    {
        return $this->image->getSize()->getHeight();
    }
    
    public function setFont(BaseFont $font, $size)
    {
        $this->addToQueue('doSetFont', func_get_args());
    }
    
    protected function doSetFont(BaseFont $font, $size)
    {
        $this->state['font'] = $font;
        $this->state['fontSize'] = $size;
    }
    
    public function addBookmark($identifier, $name, $top, $ancestorsIdentifier = null)
    {
        //not supported
    }
    
    public function copy()
    {
        $copy = clone $this;
        $copy->image = $this->image->copy();
        
        return $copy;
    }
    
    public function render()
    {
        return $this->image->get('png');
    }
}