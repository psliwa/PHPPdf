<?php

namespace PHPPdf\Test\Core\Engine\Imagine;

use PHPPdf\Core\Engine\Imagine\Image;

use Imagine\Image\Color;

use Imagine\Image\Point;

use Imagine\Image\Box;

use PHPPdf\Core\Engine\Imagine\GraphicsContext;

use PHPPdf\PHPUnit\Framework\TestCase;

class GraphicsContextTest extends TestCase
{
    private $image;
    private $imagine;
    private $drawer;
    private $gc;
    
    public function setUp()
    {
        $this->drawer = $this->getMock('Imagine\Draw\DrawerInterface');
        $this->image = $this->getMock('Imagine\Image\ImageInterface');
        $this->imagine = $this->getMock('Imagine\Image\ImagineInterface');
        $this->gc = new GraphicsContext($this->imagine, $this->image);
    }
    
    /**
     * @test
     */
    public function gsState()
    {        
        $this->gc->setFillColor('#111111');
        $this->gc->saveGS();
        $this->gc->commit();

        $state = $this->readCurrentGsState();
        $this->assertEquals('#111111', $state['fillColor']);
        
        $this->gc->setFillColor('#222222');
        $this->gc->saveGS();
        $this->gc->commit();
        
        $state = $this->readCurrentGsState();
        $this->assertEquals('#222222', $state['fillColor']);
        
        $this->gc->restoreGS();
        $this->gc->commit();
        
        $state = $this->readCurrentGsState();
        $this->assertEquals('#222222', $state['fillColor']);
        
        $this->gc->restoreGS();
        $this->gc->commit();
        
        $state = $this->readCurrentGsState();
        $this->assertEquals('#111111', $state['fillColor']);
        
        $this->gc->setFillColor('#333333');
        $this->gc->commit();
            
        $state = $this->readCurrentGsState();
        $this->assertEquals('#333333', $state['fillColor']);
        
        $this->gc->restoreGS();
        $this->gc->commit();
        
        $state = $this->readCurrentGsState();
        $this->assertEquals(null, $state['fillColor']);
    }
    
    private function readCurrentGsState()
    {
        return $this->readAttribute($this->gc, 'state');
    }
    
    /**
     * @test
     */
    public function setAttributes()
    {
        $font = $this->getMock('PHPPdf\Core\Engine\Font');
        
        $attributes = array(
            'fillColor' => '#222222',
            'lineColor' => '#333333',
            'lineWidth' => 3,
            'lineDashingPattern' => array(array(1, 2, 0)),
            'alpha' => 1,
            'font' => array($font, 13),
        );
        
        $expected = $attributes;
        $expected['lineDashingPattern'] = $expected['lineDashingPattern'][0];
        $expected['fontSize'] = $expected['font'][1];
        $expected['font'] = $expected['font'][0];
        
        foreach($attributes as $name => $value)
        {
            call_user_func_array(array($this->gc, 'set'.$name), (array) $value);
        }
        
        $this->gc->commit();
        
        $actual = $this->readCurrentGsState();
        
        ksort($expected);
        ksort($actual);
        
        $this->assertEquals($expected, $this->readCurrentGsState());
    }
    
    /**
     * @test
     */
    public function drawLine()
    {
        $x1 = 0;
        $y1 = 500;
        $x2 = 100;
        $y2 = 100;
        $color = '#000000';
        
        $this->gc->setLineColor('#000000');
        
        $width = 500;
        $height = 500;
        
        $this->setExpectedImageSize($width, $height);
                    
        $this->image->expects($this->atLeastOnce())
                    ->method('draw')
                    ->will($this->returnValue($this->drawer));
                    
        $this->drawer->expects($this->once())
                     ->method('line')
                     ->with(new Point($x1, $height - $y1), new Point($x2, $height - $y2), new Color($color))
                     ->will($this->returnValue($this->drawer));
        
        $this->gc->drawLine($x1, $y1, $x2, $y2);
        $this->gc->commit();
    }
    
    private function setExpectedImageSize($width, $height, $image = null)
    {
        $image = $image ? : $this->image;
        $box = new Box($width, $height);        
        $image->expects($this->atLeastOnce())
                    ->method('getSize')
                    ->will($this->returnValue($box));
    }
    
    /**
     * @test
     */
    public function drawImageInOriginalSize()
    {
        
        $width = $height = 500;
        $imageWidth = $imageHeight = 100;
        
        $x1 = 0;
        $y1 = 400;
        $x2 = $x1 + $imageWidth;
        $y2 = $y1 + $imageHeight;
        
        $imagineImage = $this->getMock('Imagine\Image\ImageInterface');
        $this->setExpectedImageSize($imageWidth, $imageHeight, $imagineImage);
        $this->setExpectedImageSize($width, $height);
        
        $image = new Image($imagineImage, $this->imagine);
        
        $this->image->expects($this->once())
                    ->method('paste')
                    ->with($imagineImage, new Point($x1, $height - ($y1 + $imageHeight)));
        
        $this->gc->drawImage($image, $x1, $y1, $x2, $y2);
        $this->gc->commit();
    }
}