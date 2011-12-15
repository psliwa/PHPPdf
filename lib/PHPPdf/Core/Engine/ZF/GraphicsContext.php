<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Engine\ZF;

use PHPPdf\Bridge\Zend\Pdf\Page;

use PHPPdf\Exception\Exception;
use PHPPdf\Core\Engine\AbstractGraphicsContext;
use PHPPdf\Core\Engine\GraphicsContext as BaseGraphicsContext;
use PHPPdf\Core\Engine\Color as BaseColor;
use PHPPdf\Core\Engine\Font as BaseFont;
use PHPPdf\Core\Engine\Image as BaseImage;
use Zend\Pdf\Page as ZendPage;
use Zend\Pdf\InternalType\NumericObject;
use Zend\Pdf\InternalType\StringObject;
use Zend\Pdf\InternalType\ArrayObject;
use Zend\Pdf\Font as ZendFont;
use Zend\Pdf\Resource\Font\AbstractFont as ZendResourceFont;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class GraphicsContext extends AbstractGraphicsContext
{
    private $state = array(
        'fillColor' => null,
        'lineColor' => null,
        'lineWidth' => null,
        'lineDashingPattern' => null,
        'alpha' => 1,
    );
    
    private static $originalState = array(
        'fillColor' => null,
        'lineColor' => null,
        'lineWidth' => null,
        'lineDashingPattern' => null,
        'alpha' => 1,
    );

    private $memento = null;
    
    /**
     * @var Engine
     */
    private $engine = null;

    /**
     * @var Zend\Pdf\Page
     */
    private $page;
    
    private $pageSize;
    
    private $methodInvocationsQueue = array();

    public function __construct(Engine $engine, $pageOrPageSize)
    {
        $this->engine = $engine;
        if($pageOrPageSize instanceof ZendPage)
        {
            $this->page = $pageOrPageSize;
        }
        else
        {
            $this->pageSize = $pageOrPageSize;
        }
    }

    public function getWidth()
    {
        return $this->getPage()->getWidth();
    }

    public function getHeight()
    {
        return $this->getPage()->getHeight();
    }

    protected function doClipRectangle($x1, $y1, $x2, $y2)
    {
        $this->getPage()->clipRectangle($x1, $y1, $x2, $y2);
    }

    protected function doSaveGS()
    {
        $this->getPage()->saveGS();
        $this->memento = $this->state;
    }

    protected function doRestoreGS()
    {
        $this->getPage()->restoreGS();
        $this->state = $this->memento;
        $this->memento = self::$originalState;
    }

    protected function doDrawImage(BaseImage $image, $x1, $y1, $x2, $y2)
    {
        $zendImage = $image->getWrappedImage();
        $this->getPage()->drawImage($zendImage, $x1, $y1, $x2, $y2);
    }
   
    protected function doDrawLine($x1, $y1, $x2, $y2)
    {
        $this->getPage()->drawLine($x1, $y1, $x2, $y2);
    }

    public function setFont(BaseFont $font, $size)
    {
        $this->addToQueue('doSetFont', array($font->getCurrentWrappedFont(), $size));
    }

    protected function doSetFont($fontResource, $size)
    {
        $this->getPage()->setFont($fontResource, $size);
    }

    protected function doSetFillColor($colorData)
    {
        $color = $this->getColor($colorData);
        if(!$this->state['fillColor'] || $color->getComponents() !== $this->state['fillColor']->getComponents())
        {
            $this->getPage()->setFillColor($color->getWrappedColor());
            $this->state['fillColor'] = $color;
        }
    }
    
    private function getColor($colorData)
    {
        if(is_string($colorData))
        {
            return $this->engine->createColor($colorData);
        }
        
        if(!$colorData instanceof BaseColor)
        {
            throw new Exception('Wrong color value, expected string or object of PHPPdf\Core\Engine\Color class.');
        }
        
        return $colorData;
    }

    protected function doSetLineColor($colorData)
    {
        $color = $this->getColor($colorData);
        if(!$this->state['lineColor'] || $color->getComponents() !== $this->state['lineColor']->getComponents())
        {
            $this->getPage()->setLineColor($color->getWrappedColor());
            $this->state['lineColor'] = $color;
        }
    }

    protected function doDrawPolygon(array $x, array $y, $type)
    {
        $this->getPage()->drawPolygon($x, $y, $type);
    }

    protected function doDrawText($text, $x, $y, $encoding, $wordSpacing = 0, $fillType = self::SHAPE_DRAW_FILL)
    {
        try 
        {
            if($wordSpacing === 0 && $fillType === self::SHAPE_DRAW_FILL)
            {
                $this->getPage()->drawText($text, $x, $y, $encoding);
            }
            else
            {
                $this->richDrawText($text, $x, $y, $encoding, $wordSpacing, $fillType);
            }
        }
        catch(\Zend\Pdf\Exception $e)
        {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }
    
    private function richDrawText($text, $x, $y, $encoding, $wordSpacing, $fillType)
    {
        if($this->getPage()->getFont() === null) 
        {
            throw new \Zend\Pdf\Exception\LogicException('Font has not been set');
        }
  
        if($fillType == self::SHAPE_DRAW_FILL)
        {
            $pdfFillType = 0;
        }
        elseif($fillType == self::SHAPE_DRAW_STROKE)
        {
            $pdfFillType = 1;
        }
        else
        {
            $pdfFillType = 2;
        }       
        
        $data = $this->getDataForTextDrawing($text, $x, $y, $encoding, $wordSpacing, $pdfFillType);

        $this->getPage()->rawWrite($data, 'Text');
    }
    
    private function getDataForTextDrawing($text, $x, $y, $encoding, $wordSpacing, $fillType)
    {
        $font = $this->getPage()->getFont();
        
        $xObj = new NumericObject($x);
        $yObj = new NumericObject($y);
        $wordSpacingObj = new NumericObject($wordSpacing);
        
        $data = "BT\n"
                 .  $xObj->toString() . ' ' . $yObj->toString() . " Td\n"
                 . ($fillType != 0 ? $fillType.' Tr'."\n" : '');
                 
        if($this->isFontDefiningSpaceInSingleByte($font))
        {
            $textObj = $this->createTextObject($font, $text, $encoding);

            $data .= ($wordSpacing != 0 ? $wordSpacingObj->toString().' Tw'."\n" : '')
                     .  $textObj->toString() . " Tj\n";
        }
        //Word spacing form fonts, that defines space char on 2 bytes, dosn't work
        else
        {
            $words = explode(' ', $text);

            $spaceObj = $this->createTextObject($font, ' ', $encoding);
            
            foreach($words as $word)
            {
                $textObj = $this->createTextObject($font, $word, $encoding);
                $data .= '0 Tc'."\n"
                		 . $textObj->toString(). " Tj\n"
                		 . $wordSpacingObj->toString() . " Tc\n"
                		 . $spaceObj->toString() ." Tj\n";
            }
        }
        
        $data .= "ET\n";
                 
        return $data;
    }
    
    private function createTextObject(ZendResourceFont $font, $text, $encoding)
    {
        return new StringObject($font->encodeString($text, $encoding));
    }
    
    private function isFontDefiningSpaceInSingleByte(ZendResourceFont $font)
    {
        return $font->getFontType() === ZendFont::TYPE_STANDARD;
    }

    public function __clone()
    {
        if($this->page)
        {
            $this->page = clone $this->getPage();
        }
    }

    public function getPage()
    {
        if(!$this->page)
        {
            $this->page = new Page($this->pageSize);
            $this->pageSize = null;
        }
        return $this->page;
    }

    protected function doDrawRoundedRectangle($x1, $y1, $x2, $y2, $radius, $fillType = self::SHAPE_DRAW_FILL_AND_STROKE)
    {
        $this->getPage()->drawRoundedRectangle($x1, $y1, $x2, $y2, $radius, $this->translateFillType($fillType));
    }
    
    private function translateFillType($fillType)
    {
        switch($fillType)
        {
            case self::SHAPE_DRAW_STROKE:
                return ZendPage::SHAPE_DRAW_STROKE;
            case self::SHAPE_DRAW_FILL:
                return ZendPage::SHAPE_DRAW_FILL;
            case self::SHAPE_DRAW_FILL_AND_STROKE:
                return ZendPage::SHAPE_DRAW_FILL_AND_STROKE;
            default:
                throw new \InvalidArgumentException(sprintf('Invalid filling type "%s".', $fillType));
        }
    }
    
    protected function doSetLineWidth($width)
    {
        if(!$this->state['lineWidth'] || $this->state['lineWidth'] != $width)
        {
            $this->getPage()->setLineWidth($width);
            $this->state['lineWidth'] = $width;
        }
    }
    
    protected function doSetLineDashingPattern($pattern)
    {
        switch($pattern)
        {
            case self::DASHING_PATTERN_DOTTED:
                $pattern = array(1, 2);
                break;
        }
        
        if($this->state['lineDashingPattern'] === null || $this->state['lineDashingPattern'] !== $pattern)
        {
            $this->getPage()->setLineDashingPattern($pattern);
            $this->state['lineDashingPattern'] = $pattern;
        }
    }

    protected function doUriAction($x1, $y1, $x2, $y2, $uri)
    {
        try
        {
            $uriAction = \Zend\Pdf\Action\Uri::create($uri);
            
            $annotation = $this->createAnnotationLink($x1, $y1, $x2, $y2, $uriAction);
            
            $this->getPage()->attachAnnotation($annotation);
        }
        catch(\Zend\Pdf\Exception $e)
        {
            throw new Exception(sprintf('Error wile adding uri action with uri="%s"', $uri), 0, $e);
        }
    }
    
    protected function doGoToAction(BaseGraphicsContext $gc, $x1, $y1, $x2, $y2, $top)
    {
        try
        {
            $destination = \Zend\Pdf\Destination\FitHorizontally::create($gc->getPage(), $top);   
            
            $annotation = $this->createAnnotationLink($x1, $y1, $x2, $y2, $destination);
            
            $this->getPage()->attachAnnotation($annotation);
        }
        catch(\Zend\Pdf\Exception $e)
        {
            throw new Exception('Error while adding goTo action', 0, $e);
        }        
    }
    
    private function createAnnotationLink($x1, $y1, $x2, $y2, $target)
    {
        $annotation = \Zend\Pdf\Annotation\Link::create($x1, $y1, $x2, $y2, $target);
        $annotationDictionary = $annotation->getResource();
        
        $border = new ArrayObject();
        $zero = new NumericObject(0);
        $border->items[] = $zero;
        $border->items[] = $zero;
        $border->items[] = $zero;
        $border->items[] = $zero;
        $annotationDictionary->Border = $border;

        return $annotation;
    }
    
    public function addBookmark($identifier, $name, $top, $parentIdentifier = null)
    {
        try
        {   
            $destination = \Zend\Pdf\Destination\FitHorizontally::create($this->getPage(), $top);
            $action = \Zend\Pdf\Action\GoToAction::create($destination);
            
            $outline = \Zend\Pdf\Outline\AbstractOutline::create($name, $action);
            
            $this->engine->registerOutline($identifier, $outline);     
            
            $this->addToQueue('doAddBookmark', array($identifier, $outline, $parentIdentifier));
        }
        catch(\Zend\Pdf\Exception $e)
        {
            throw new Exception('Error while bookmark adding', 0, $e);
        }
    }

    protected function doAddBookmark($identifier, \Zend\Pdf\Outline\AbstractOutline $outline, $parentIdentifier = null)
    {
        try
        {            
            if($parentIdentifier !== null)
            {
                $parent = $this->engine->getOutline($parentIdentifier);
                $parent->childOutlines[] = $outline;
            }
            else
            {
                $this->engine->getZendPdf()->outlines[] = $outline;
            }
        }
        catch(\Zend\Pdf\Exception $e)
        {
            throw new Exception('Error while bookmark adding', 0, $e);
        }
    }
    
    protected function doAttachStickyNote($x1, $y1, $x2, $y2, $text)
    {
        $annotation = \Zend\Pdf\Annotation\Text::create($x1, $y1, $x2, $y2, $text);
        $this->getPage()->attachAnnotation($annotation);
    }
    
    protected function doSetAlpha($alpha)
    {
        if($this->state['alpha'] != $alpha)
        {
            $this->getPage()->setAlpha($alpha);
            $this->state['alpha'] = $alpha;
        }
    }

    protected function doRotate($x, $y, $angle)
    {
        $this->getPage()->rotate($x, $y, $angle);
    }
    
    public function copy()
    {
        $this->commit();
        $gc = clone $this;
        if($this->page)
        {
            $gc->page = clone $this->getPage();
        }
        
        return $gc;
    }
}