<?php

namespace PHPPdf\Core\Engine\Imagine;

use PHPPdf\Core\Engine\AbstractGraphicsContext;
use PHPPdf\Core\Engine\GraphicsContext as BaseGraphicsContext;
use PHPPdf\Core\Engine\Image as BaseImage;
use PHPPdf\Core\Engine\Font as BaseFont;

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
    
    public function __construct()
    {
        $this->state = self::$originalState;
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
        
    }
    
    protected function doDrawLine($x1, $y1, $x2, $y2)
    {
        
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
        
    }
    
    protected function doDrawText($text, $x, $y, $encoding, $wordSpacing = 0, $fillType = self::SHAPE_DRAW_FILL)
    {
        
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
    
    protected function doRotate($x, $y, $angle)
    {
        
    }
    
    public function getWidth()
    {
        
    }
    
    public function getHeight()
    {
        
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
        
    }
}