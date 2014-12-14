<?php

namespace PHPPdf\Test\Core\Engine\Imagine\GraphicsContextTest;

use PHPPdf\Core\Engine\Imagine\Font;
use PHPPdf\Core\Engine\EmptyImage;
use PHPPdf\Core\Engine\Imagine\Image;
use Imagine\Image\Color;
use PHPPdf\Bridge\Imagine\Image\Point;
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
        $expected['fontStyle'] = null;
        $expected['clips'] = array();
        
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
        $image->expects($this->any())
                    ->method('getSize')
                    ->will($this->returnValue($box));
    }

    /**
     * @test
     * @dataProvider drawPolygonProvider
     */
    public function drawPolygon(array $x, array $y, $fillType)
    {
        $width = $height = 500;
        $expectedFillColor = '#dddddd';
        $expectedLineColor = '#cccccc';
        
        $this->image->expects($this->atLeastOnce())
                    ->method('draw')
                    ->will($this->returnValue($this->drawer));
                    
        $this->setExpectedImageSize($width, $height);
        
        $expectedCoords = array();
        
        foreach($y as $i => $coord)
        {
            $expectedCoords[] = new Point($x[$i], $height - $coord);
        }
        
        $expectedFill = $fillType == GraphicsContext::SHAPE_DRAW_FILL;
        $expectedPolygons = array();
        
        if($fillType > 0)
        {
            $expectedPolygons[] = array($expectedFillColor, true);
        }
        
        if($fillType == 0 || $fillType == 2)
        {
            $expectedPolygons[] = array($expectedLineColor, false);
        }
        
        foreach($expectedPolygons as $at => $polygon)
        {
            list($expectedColor, $expectedFill) = $polygon;
            $this->drawer->expects($this->at($at))
                         ->method('polygon')
                         ->with($expectedCoords, new Color($expectedColor), $expectedFill)
                         ->will($this->returnValue($this->drawer));
        }
        
                     
        $this->gc->setFillColor($expectedFillColor);
        $this->gc->setLineColor($expectedLineColor);
        $this->gc->drawPolygon($x, $y, $fillType);
        
        $this->gc->commit();
    }
    
    public function drawPolygonProvider()
    {
        return array(
            array(
                array(0, 50, 50, 0),
                array(300, 300, 100, 100),
                GraphicsContext::SHAPE_DRAW_FILL,
            ),
            array(
                array(0, 50, 50, 0),
                array(300, 300, 100, 100),
                GraphicsContext::SHAPE_DRAW_STROKE,
            ),
            array(
                array(0, 50, 50, 0),
                array(300, 300, 100, 100),
                GraphicsContext::SHAPE_DRAW_FILL_AND_STROKE,
            ),
        );
    }
    
    /**
     * @test
     * @dataProvider alphaProvider
     */
    public function drawText($alpha, $expectedImagineAlpha)
    {
        $text = 'some text';
        $fontSize = 12;
        $color = '#000000';
        
        $x = 0;
        $y = 100;
        
        $width = $height = 200;
        
        $font = $this->getMockBuilder('PHPPdf\Core\Engine\Imagine\Font')
                     ->setMethods(array('getWrappedFont'))
                     ->disableOriginalConstructor()
                     ->getMock();
                     
        $imagineFont = $this->getMockBuilder('Imagine\Image\AbstractFont')
                            ->disableOriginalConstructor()
                            ->getMock();
        
        $font->expects($this->once())
             ->method('getWrappedFont')
             ->with(new Color($color, $expectedImagineAlpha), $fontSize)
             ->will($this->returnValue($imagineFont));
             
        $this->image->expects($this->once())
                    ->method('draw')
                    ->will($this->returnValue($this->drawer));
                    
        $box = new Box($width, $height);
        $this->image->expects($this->any())
                    ->method('getSize')
                    ->will($this->returnValue($box));
                    
        $expectedPosition = new Point($x, $height - $y - $fontSize);
                    
        $this->drawer->expects($this->once())
                     ->method('text')
                     ->with($text, $imagineFont, $expectedPosition);
                     
        $this->gc->setAlpha($alpha);
        $this->gc->setFont($font, $fontSize);
        $this->gc->setFillColor($color);
        $this->gc->drawText($text, $x, $y, 'utf-8');
        $this->gc->commit();
    }
    
    public function alphaProvider()
    {
        return array(
            array(1, 0),
            array(0.2, 80),
        );
    }
    
    /**
     * @test
     */
    public function setFont()
    {
        $normalFont = $this->getMockBuilder('Imagine\Image\AbstractFont')
                           ->disableOriginalConstructor()
                           ->getMock();
                           
        $boldFont = clone $normalFont;
                           
        $this->imagine->expects($this->at(0))
                      ->method('font')
                      ->with('normal', $this->anything(), $this->anything())
                      ->will($this->returnValue($normalFont));

        $this->imagine->expects($this->at(1))
                      ->method('font')
                      ->with('bold', $this->anything(), $this->anything())
                      ->will($this->returnValue($boldFont));
        
        $font = new Font(array(
            Font::STYLE_NORMAL => 'normal',
            Font::STYLE_BOLD => 'bold',
        ), $this->imagine);
        
        $this->drawer->expects($this->at(0))
                     ->method('text')
                     ->with($this->anything(), $normalFont, $this->anything());
        $this->drawer->expects($this->at(1))
                     ->method('text')
                     ->with($this->anything(), $boldFont, $this->anything());
        $this->image->expects($this->any())
                    ->method('getSize')
                    ->will($this->returnValue(new Box(500, 500)));
        $this->image->expects($this->any())
                    ->method('draw')
                    ->will($this->returnValue($this->drawer));
                     
        $font->setStyle(Font::STYLE_NORMAL);
        $this->gc->setFillColor('#000000');
        $this->gc->setFont($font, 12);
        $this->gc->drawText('abc', 100, 100, 'utf-8');
        $font->setStyle(Font::STYLE_BOLD);
        $this->gc->setFont($font, 12);
        $this->gc->drawText('abc', 100, 100, 'utf-8');
        
        $this->gc->commit();
    }
    
    /**
     * @test
     */
    public function clipRectangle()
    {
        $width = 100;
        $height = 200;
        
        $x1 = 25;
        $y1 = 175;
        $x2 = 75;
        $y2 = 25;
        
        $rectangleWidth = $x2 - $x1;
        $rectangleHeight = $y1 - $y2;
        
        $this->setExpectedImageSize($width, $height);
                    
        $rectangleImage = $this->getMock('Imagine\Image\ImageInterface');
        $this->setExpectedImageSize($rectangleWidth, $rectangleHeight, $rectangleImage);
        $this->imagine->expects($this->once())
                      ->method('create')
                      ->with(new Box($rectangleWidth, $rectangleHeight))
                      ->will($this->returnValue($rectangleImage));
                      
       $this->gc->saveGS();
       $this->gc->clipRectangle($x1, $y1, $x2, $y2);
       $this->gc->commit();
       
       $this->image->expects($this->once())
                   ->method('paste')
                   ->with($rectangleImage, new Point($x1, $height - $y1));
       
       $this->gc->restoreGS();
       $this->gc->commit();
    }
    
    /**
     * @test
     */
    public function ignoreEmptyImage()
    {
        $image = EmptyImage::getInstance();
        
        $this->imagine->expects($this->never())
                      ->method('paste');
                      
        $this->gc->drawImage($image, 50, 50, 100, 10);
        $this->gc->commit();
    }
}