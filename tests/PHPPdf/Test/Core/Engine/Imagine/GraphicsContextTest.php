<?php

namespace PHPPdf\Test\Core\Engine\Imagine;

use PHPPdf\Core\Engine\Imagine\GraphicsContext;

use PHPPdf\PHPUnit\Framework\TestCase;

class GraphicsContextTest extends TestCase
{
    private $image;
    private $gc;
    
    public function setUp()
    {
        $this->image = $this->getMock('Imagine\Image\ImageInterface');
        $this->gc = new GraphicsContext($this->image);
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
}