<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Engine\Imagine;

use Imagine\Image\BoxInterface;
use Imagine\Image\PointInterface;
use PHPPdf\Bridge\Imagine\Image\Point;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use PHPPdf\Bridge\Imagine\Rectangle;
use PHPPdf\Core\Engine\AbstractGraphicsContext;
use PHPPdf\Core\Engine\GraphicsContext as BaseGraphicsContext;
use PHPPdf\Core\Engine\Image as BaseImage;
use PHPPdf\Core\Engine\Font as BaseFont;
use Imagine\Image\Color as ImagineColor;
use Imagine\Image\FontInterface as ImagineFont;
use Zend\Barcode\Object\ObjectInterface as Barcode;

/**
 * Graphics context for Imagine
 * 
 * * TODO: lineDashingPattern for doDrawPolygon
 * * TODO: support for lineWidth
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class GraphicsContext extends AbstractGraphicsContext
{
    private static $originalState = array(
        'fillColor' => null,
        'lineColor' => null,
        'lineWidth' => null,
        'lineDashingPattern' => self::DASHING_PATTERN_SOLID,
        'alpha' => 1,
        'font' => null,
        'fontStyle' => null,
        'fontSize' => null,
        'clips' => array(),
    );
    
    private $stateStack = array();
    
    private $state = array();
    
    private $image;
    
    /**
     * @var ImagineInterface
     */
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
            $clip = array($this->image, new Point(0, 0), 0);
        }
        
        return $clip;
    }
    
    protected function doClipRectangle($x1, $y1, $x2, $y2)
    {
        $width = $x2 - $x1;
        $height = $y1 - $y2;
        
        $image = $this->imagine->create(new Box($width, $height));
        
        $point = new Point($x1, $this->convertYCoord($y1));
        
        $this->state['clips'][] = array($image, $point, 0, null);
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
            list($clipImage, $clipPoint, $angle, $p) = $clip;
            
            if($angle != 0)
            {
                $clipImage->rotate($angle);
                $clipPoint = $p;
            }

            $this->pasteImage($image, $clipImage, $this->translatePoint($point, $clipPoint->getX(), $clipPoint->getY()));
        }
        
        $this->state = $state;
    }
    
    private function rotatePoint($angle, Point $p, Point $o)
    {
        $pXp = $p->getX() - $o->getX();
        $pYp = $p->getY() - $o->getY();
        
        $oXp = $pXp * cos($angle) - $pYp * sin($angle);
        $oYp = $pXp * sin($angle) - $pYp * cos($angle);
        
        $oXp += $o->getX();
        $oYp += $o->getY();
        
        return new Point($oXp, $oYp);
    }
    
    protected function doDrawImage(BaseImage $image, $x1, $y1, $x2, $y2)
    {
        $requestedHeight = $y2 - $y1;
        $requestedWidth = $x2 - $x1;

        /**
         * @var ImageInterface $imagineImage
         */
        $imagineImage = $image->getWrappedImage();
        
        $box = $imagineImage->getSize();
        $imageHeight = $box->getHeight();
        $imageWidth = $box->getWidth();
        
        if($requestedHeight != $imageHeight || $requestedWidth != $imageWidth)
        {
            $newBox = new Box($requestedWidth, $requestedHeight);
            $imagineImage->resize($newBox);
        }
        
        $y = $this->convertYCoord($y2);

        /**
         * @var ImageInterface $image
         */
        list($image, $basePoint) = $this->getCurrentClip();

        $point = $this->translatePoint($basePoint, $x1, $y);

        $this->pasteImage($image, $imagineImage, $point);
    }

    private function pasteImage(ImageInterface $image, ImageInterface $imageToPaste, PointInterface $pastePoint)
    {
        if(!$this->boxContains($image->getSize(), $imageToPaste->getSize(), $pastePoint))
        {
            $rectangle = Rectangle::createWithSize($image->getSize())
                ->intersection(Rectangle::create($pastePoint, $imageToPaste->getSize()));

            if($rectangle !== null)
            {
                $croppingPoint = new Point(
                    $rectangle->getStartingPoint()->getX() - $pastePoint->getX(),
                    $rectangle->getStartingPoint()->getY() - $pastePoint->getY()
                );

                $imageToPaste->crop($croppingPoint, $rectangle->getSize());

                $image->paste($imageToPaste, $this->ensureNonNegativePoint($pastePoint));
            }
        }
        else
        {
            $image->paste($imageToPaste, $pastePoint);
        }
    }

    private function boxContains(BoxInterface $box, BoxInterface $containedBox, PointInterface $point)
    {
        return
            $point->getX() >= 0 && $point->getY() >= 0 && $point->getX() < $box->getWidth() && $point->getY() < $box->getHeight() &&
            $box->getWidth() >= $containedBox->getWidth() + $point->getX() &&
            $box->getHeight() >= $containedBox->getHeight() + $point->getY();
    }

    private function ensureNonNegativePoint(PointInterface $point)
    {
        if($point->getX() < 0 || $point->getY() < 0)
        {
            return new Point(max(0, $point->getX()), max(0, $point->getY()));
        }

        return $point;
    }
    
    private function translatePoint(PointInterface $point, $x, $y)
    {
        return new Point($x - $point->getX(), $y - $point->getY());
    }
    
    private function convertYCoord($y)
    {
        return $this->getHeight() - $y;
    }
    
    //TODO: width of line
    protected function doDrawLine($x1, $y1, $x2, $y2)
    {        
        $lineStyle = $this->state['lineDashingPattern'];

        if($lineStyle === self::DASHING_PATTERN_SOLID)
        {
            list($image, $point) = $this->getCurrentClip();
            $image->draw()->line($this->translatePoint($point, $x1, $this->convertYCoord($y1)), $this->translatePoint($point, $x2, $this->convertYCoord($y2)), $this->createColor($this->state['lineColor']));
        }
        else
        {
            $this->doDrawDottedLine($x1, $y1, $x2, $y2);
        }
    }
    
    private function doDrawDottedLine($x1, $y1, $x2, $y2)
    {
        list($image, $point) = $this->getCurrentClip();
        
        $lineStyle = $this->state['lineDashingPattern'];
        $color = $this->createColor($this->state['lineColor']);
        
        $y1 = $this->convertYCoord($y1);
        $y2 = $this->convertYCoord($y2);
        
        $rotated = false;
        
        if($x1 == $x2)
        {
            //rotate coordinate system by 90 deegres
            $tmp1 = $y1;
            $y1 = $x1;
            $tmp2 = $y2;
            $y2 = $x2;
            $x2 = $tmp2;
            $x1 = $tmp1;
            
            $rotated = true;
        }
        
        $pattern = $lineStyle === self::DASHING_PATTERN_DOTTED ? array(1, 2) : (array) $lineStyle;
        
        $on = false;            
        $patternLength = count($pattern);
        $currentX = min($x1, $x2);  

        if($currentX !== $x1)
        {
            $x2 = $x1;
            $x1 = $currentX;
            
            $tmp = $y1;
            $y1 = $y2;
            $y2 = $tmp;
        }
        
        $factor = ($y2 - $y1)/($x2 - $x1);
        
        for($i=1; $currentX < $x2; $i++)
        {
            $length = $pattern[$i % $patternLength];
            $nextX = $currentX + $length;

            if($on)
            {
                $nextY = $this->linear($nextX, $x1, $y1, $factor);
                $currentY = $this->linear($currentX, $x1, $y1, $factor);
                $image->draw()->line($this->translatePoint($point, $rotated ? $currentY : $currentX, $rotated ? $currentX : $currentY), $this->translatePoint($point, $rotated ? $nextY : $nextX, $rotated ? $nextX : $nextY), $color);
            }
            
            $currentX = $nextX;                
            $on = !$on;
        }
    }
    
    private function linear($x, $x1, $y1, $factor)
    {
        // y = (y2 - y1)(x - x1)/(x2 - x1) + y1
        return $factor*($x - $x1) + $y1;;
    }
    
    private function createColor($color)
    {
        $alpha = (int) (100 - $this->state['alpha'] * 100);
        
        return new ImagineColor($color, $alpha);
    }
    
    protected function doSetFillColor($colorData)
    {
        $this->state['fillColor'] = $colorData;
    }
    
    protected function doSetLineColor($colorData)
    {
        $this->state['lineColor'] = $colorData;
    }
    
    //TODO: width of line
    protected function doDrawPolygon(array $x, array $y, $type)
    {
        list($image, $point) = $this->getCurrentClip();
        
        $color = $this->createColor($this->state['fillColor']);
        
        $fill = $type > 0;
        
        $coords = array();
        
        foreach($x as $i => $coord)
        {
            $coords[] = $this->translatePoint($point, $coord, $this->convertYCoord($y[$i]));
        }
        
        $polygons = array();
        
        if($this->isFillShape($type))
        {
            $polygons[] = array($this->createColor($this->state['fillColor']), true);
        }
        
        if($this->isStrokeShape($type))
        {
            $polygons[] = array($this->createColor($this->state['lineColor']), false);
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
        $color = $this->state['fillColor'];
        $size = $this->state['fontSize'];
        $color = $this->createColor($color);
        $font->setStyle($this->state['fontStyle']);
        $imagineFont = $font->getWrappedFont($color, $size);
        
        list($image, $point) = $this->getCurrentClip();
        
        $position = $this->translatePoint($point, $x, $this->convertYCoord($y) - $size);
        
        if($wordSpacing === 0)
        {
            $image->draw()->text($text, $imagineFont, $position);
        }
        else
        {
            $this->richDrawText($text, $image, $wordSpacing, $imagineFont, $position);
        }
    }
    
    private function richDrawText($text, ImageInterface $image, $wordSpacing, ImagineFont $font, Point $point)
    {
        $words = preg_split('/\s+/', $text);
        
        $wordSpacing = $wordSpacing + $this->getWidthOfSpaceChar($font);

        foreach($words as $word)
        {
            if($word !== '')
            {
                $box = $font->box($word);
                $image->draw()->text($word, $font, $point);
                $point = new Point($point->getX() + $box->getWidth() + $wordSpacing, $point->getY());
            }
        }
    }
    
    private function getWidthOfSpaceChar(ImagineFont $font)
    {
        return $font->box(' ')->getWidth();
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
            $color = $this->createColor($this->state['lineColor']);
            
            
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
            $color = $this->createColor($this->state['fillColor']);
            
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
    
    protected function doRotate($x, $y, $angle)
    {
        list($currentImage, $point) = $this->getCurrentClip();
        $size = $currentImage->getSize();
        $y = $size->getHeight() - $y;
        
        $pointsToRotate = array(
            new Point(0, 0),
            new Point($size->getWidth(), 0),
            new Point($size->getWidth(), $size->getHeight()),
            new Point(0, $size->getHeight()),
        );
        
        $xs = array();
        $ys = array();
        
        $rotatePoint = new Point($x, $y);
        
        foreach($pointsToRotate as $pointToRotate)
        {
            $rotatedPoint = $this->rotatePoint($angle, $pointToRotate, $rotatePoint);
            $xs[] = $rotatedPoint->getX();
            $ys[] = $rotatedPoint->getY();
        }
        
        $firstPoint = new Point(min($xs), min($ys));
        $diagonalPoint = new Point(max($xs), max($ys));
        $middlePoint = new Point(($diagonalPoint->getX() - $firstPoint->getX())/2, ($diagonalPoint->getY() - $firstPoint->getY())/2);
        
        $width = $diagonalPoint->getX() - $firstPoint->getX();
        $height = $diagonalPoint->getY() - $firstPoint->getY();

        $image = $this->imagine->create($size);
              
        $angleInDegrees = rad2deg($angle);

        $clipPoint = new Point(0, 0);
        $rotatePoint = new Point(-$middlePoint->getX() + $x, -$middlePoint->getY() + $y);
                       
        $this->state['clips'][] = array($image, $clipPoint, $angleInDegrees, $rotatePoint);
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
        $this->addToQueue('doSetFont', array($font, $size, $font->getCurrentStyle()));
    }
    
    protected function doSetFont(BaseFont $font, $size, $style)
    {
        $this->state['font'] = $font;
        $this->state['fontSize'] = $size;
        $this->state['fontStyle'] = $style;
    }
    
    public function addBookmark($identifier, $name, $top, $ancestorsIdentifier = null)
    {
        //not supported
    }
    
    protected function doDrawBarcode($x, $y, Barcode $barcode)
    {
        $renderer = new \Zend\Barcode\Renderer\Image(array());
        $imageResource = imagecreate($barcode->getWidth(true) + 1, $barcode->getHeight(true) + 1);
        
        $renderer->setResource($imageResource);
        $renderer->setBarcode($barcode);
        $renderer->draw();
        
        ob_start();
        imagepng($imageResource);
        $image = ob_get_clean();
        @imagedestroy($image);
        
        $image = $this->imagine->load($image);
        $image = $image->resize(new Box($image->getSize()->getWidth()/2, $image->getSize()->getHeight()/2));
        list($currentImage, $point) = $this->getCurrentClip();
        $currentImage->paste($image, $this->translatePoint($point, $x, $this->convertYCoord($y)));
    }

    public function copy()
    {
        $this->commit();

        $copy = clone $this;
        $copy->image = $this->image->copy();
        
        return $copy;
    }
    
    public function render($format, array $options = array())
    {
        return $this->image->get($format, $options);
    }
    
    protected function doDrawEllipse($x, $y, $width, $height, $fillType)
    {
        list($image, $point) = $this->getCurrentClip();
        
        $point = $this->translatePoint($point, $x, $this->convertYCoord($y));
        $box = new Box($width, $height);
        
        if($fillType === self::SHAPE_DRAW_FILL || $fillType === self::SHAPE_DRAW_FILL_AND_STROKE)
        {        
            $color = $this->createColor($this->state['fillColor']);
            $image->draw()->ellipse($point, $box, $color, true);          
        }
        
        if($fillType === self::SHAPE_DRAW_STROKE || $fillType === self::SHAPE_DRAW_FILL_AND_STROKE)
        {        
            $color = $this->createColor($this->state['lineColor']);
            $image->draw()->ellipse($point, $box, $color, false);            
        }
    }
    
    protected function doDrawArc($x, $y, $width, $height, $start, $end, $fillType)
    {
        list($image, $point) = $this->getCurrentClip();

        $point = $this->translatePoint($point, $x, $this->convertYCoord($y));
        $color = $this->createColor($this->state['fillColor']);
        $box = new Box($width, $height);
        
        if($fillType === self::SHAPE_DRAW_FILL || $fillType === self::SHAPE_DRAW_FILL_AND_STROKE)
        {
            $image->draw()->pieSlice($point, $box, $start, $end, $color, true);
        }
        
        if($fillType === self::SHAPE_DRAW_STROKE || $fillType === self::SHAPE_DRAW_FILL_AND_STROKE)
        {
            $image->draw()->pieSlice($point, $box, $start, $end, $color, false);
        }
    }
}