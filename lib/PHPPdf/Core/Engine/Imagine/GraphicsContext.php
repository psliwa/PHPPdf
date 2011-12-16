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
        'clips' => array(),
    );
    
    private $stateStack = array();
    
    private $state = array();
    
    private $image;
    private $imagine;
    
    private $clips = array();
    
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
    
    /**
     * @return ImageInterface
     */
    private function getCurrentClip()
    {
        $stack = $this->stateStack;
        
        array_unshift($stack, $this->state);

        $clip = null;
        
        foreach($stack as $state)
        {            
            if($clip = end($state['clips']))
            {
                break;
            }
        }
        
        if(!$clip)
        {
            $clip = array($this->image, new Point(0, 0));
        }
        
        return $clip;
    }
    
    protected function doClipRectangle($x1, $y1, $x2, $y2)
    {
        $width = $x2 - $x1;
        $height = $y1 - $y2;
        
        $image = $this->imagine->create(new Box($width, $height));
        
        $point = new Point($x1, $this->convertYCoord($y1));
        
        $this->state['clips'][] = array($image, $point);
    }
    
    protected function doSaveGS()
    {
        array_unshift($this->stateStack, $this->state);
        $this->state['clips'] = array();
    }
    
    protected function doRestoreGS()
    {
        $state = array_shift($this->stateStack);
        
        if($state === null)
        {
            $state = self::$originalState;
        }
        
        $clips = $this->state['clips'];
        $this->state['clips'] = array();
        
        list($image, $point) = $this->getCurrentClip();
        
        foreach($clips as $clip)
        {
            list($clipImage, $clipPoint) = $clip;
            
            $image->paste($clipImage, $this->translatePoint($point, $clipPoint->getX(), $clipPoint->getY()));
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
        
        $y = $this->convertYCoord($y1 + $height);
        
        list($image, $point) = $this->getCurrentClip();

        $image->paste($imagineImage, $this->translatePoint($point, $x1, $y));
    }
    
    private function translatePoint(Point $point, $x, $y)
    {
        return new Point($x - $point->getX(), $y - $point->getY());
    }
    
    private function convertYCoord($y)
    {
        return $this->getHeight() - $y;
    }
    
    protected function doDrawLine($x1, $y1, $x2, $y2)
    {
        //TODO: throw exception if lineColor is not set
        //TODO: line width
        $color = new ImagineColor($this->state['lineColor']);
        
        list($image, $point) = $this->getCurrentClip();
        
        $image->draw()->line($this->translatePoint($point, $x1, $this->convertYCoord($y1)), $this->translatePoint($point, $x2, $this->convertYCoord($y2)), $color);
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
        list($image, $point) = $this->getCurrentClip();
        
        $color = new ImagineColor($this->state['fillColor']);
        
        $fill = $type > 0;
        
        $coords = array();
        
        foreach($x as $i => $coord)
        {
            $coords[] = $this->translatePoint($point, $coord, $this->convertYCoord($y[$i]));
        }
        
        $polygons = array();
        
        if($this->isFillShape($type))
        {
            $polygons[] = array(new ImagineColor($this->state['fillColor']), true);
        }
        
        if($this->isStrokeShape($type))
        {
            $polygons[] = array(new ImagineColor($this->state['lineColor']), false);
        }
        
        foreach($polygons as $polygon)
        {
            list($color, $fill) = $polygon;
            $image->draw()->polygon($coords, $color, $fill);
        }
    }
    
    private function isFillShape($type)
    {
        return in_array($type, array(self::SHAPE_DRAW_FILL, self::SHAPE_DRAW_FILL_AND_STROKE));
    }
    
    private function isStrokeShape($type)
    {
        return in_array($type, array(self::SHAPE_DRAW_FILL_AND_STROKE, self::SHAPE_DRAW_STROKE));
    }
    
    protected function doDrawText($text, $x, $y, $encoding, $wordSpacing = 0, $fillType = self::SHAPE_DRAW_FILL)
    {
        $font = $this->state['font'];
        $color = $this->state['lineColor'];
        $size = $this->state['fontSize'];
        
        $imagineFont = $font->getWrappedFont($color, $size);
        
        list($image, $point) = $this->getCurrentClip();
        
        $position = $this->translatePoint($point, $x, $this->convertYCoord($y) - $size);
        
        $image->draw()->text($text, $imagineFont, $position);
    }
    
    protected function doDrawRoundedRectangle($x1, $y1, $x2, $y2, $radius, $fillType = self::SHAPE_DRAW_FILL_AND_STROKE)
    {
        list($image, $point) = $this->getCurrentClip();
        
        $leftStartPoint = $this->translatePoint($point, $x1, $this->convertYCoord($y1 + $radius));
        $leftEndPoint = $this->translatePoint($point, $x1, $this->convertYCoord($y2 - $radius));
        $rightStartPoint = $this->translatePoint($point, $x2, $this->convertYCoord($y1 + $radius));
        $rightEndPoint = $this->translatePoint($point, $x2, $this->convertYCoord($y2 - $radius));
        $bottomStartPoint = $this->translatePoint($point, $x1 + $radius, $this->convertYCoord($y1));
        $bottomEndPoint = $this->translatePoint($point, $x2 - $radius, $this->convertYCoord($y1));
        $topStartPoint = $this->translatePoint($point, $x1 + $radius, $this->convertYCoord($y2));
        $topEndPoint = $this->translatePoint($point, $x2 - $radius, $this->convertYCoord($y2));
        
        $leftTopCircleCenter = $this->translatePoint($point, $x1 + $radius, $this->convertYCoord($y2 - $radius));
        $rightTopCircleCenter = $this->translatePoint($point, $x2 - $radius, $this->convertYCoord($y2 - $radius));
        $rightBottomCircleCenter = $this->translatePoint($point, $x2 - $radius, $this->convertYCoord($y1 + $radius));
        $leftBottomCircleCenter = $this->translatePoint($point, $x1 + $radius, $this->convertYCoord($y1 + $radius));
        
        $circleBox = new Box($radius*2, $radius*2);
        
        if($this->isStrokeShape($fillType))
        {
            $color = new ImagineColor($this->state['lineColor']);
            
            
            $image->draw()->line($leftStartPoint, $leftEndPoint, $color)
                  ->line($rightStartPoint, $rightEndPoint, $color)
                  ->line($topStartPoint, $topEndPoint, $color)
                  ->line($bottomStartPoint, $bottomEndPoint, $color)
                  ->arc($leftTopCircleCenter, $circleBox, -180, -90, $color)
                  ->arc($rightTopCircleCenter, $circleBox, -90, 0, $color)
                  ->arc($rightBottomCircleCenter, $circleBox, 0, 90, $color)
                  ->arc($leftBottomCircleCenter, $circleBox, 90, 180, $color)
            ;
            
        }
        
        if($this->isFillShape($fillType))
        {
            $color = new ImagineColor($this->state['fillColor']);
            
            $image->draw()->polygon(array($leftStartPoint, $rightStartPoint, $rightEndPoint, $leftEndPoint), $color, true)
                  ->polygon(array($topStartPoint, $topEndPoint, $bottomEndPoint, $bottomStartPoint), $color, true)
                  ->ellipse($leftTopCircleCenter, $circleBox, $color, true)
                  ->ellipse($rightTopCircleCenter, $circleBox, $color, true)
                  ->ellipse($rightBottomCircleCenter, $circleBox, $color, true)
                  ->ellipse($leftBottomCircleCenter, $circleBox, $color, true)
            ;
        }
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