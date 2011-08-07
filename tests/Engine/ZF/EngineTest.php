<?php

namespace PHPPdf\Test\Engine\ZF;

use PHPPdf\Engine\Font;

use PHPPdf\Engine\ZF\Engine;

class EngineTest extends \TestCase
{
    private $engine;
    private $zendPdf;
    
    public function setUp()
    {
        $this->zendPdf = new \Zend_Pdf();
        $this->engine = new Engine($this->zendPdf);
    }
    
    /**
     * @test
     */
    public function createColor()
    {
        $color = $this->engine->createColor('#000000');
        
        $this->assertInstanceOf('PHPPdf\Engine\ZF\Color', $color);
        
        $this->assertEquals(array(0), $color->getComponents());
    }
    
    /**
     * @test
     */
    public function createImage()
    {
        $image = $this->engine->createImage(__DIR__.'/../../resources/domek.jpg');
        
        $this->assertInstanceOf('PHPPdf\Engine\ZF\Image', $image);
    }
    
    /**
     * @test
     * @dataProvider fontProvider
     */
    public function createFont($fontData)
    {
        $font = $this->engine->createFont($fontData);
        
        $this->assertInstanceOf('PHPPdf\Engine\ZF\Font', $font);
        
        foreach($fontData as $style => $data)
        {
            $this->assertTrue($font->hasStyle($style));
        }
    }
    
    public function fontProvider()
    {
        $resourcesDir = __DIR__.'/../../resources';
        return array(
            array(
                array(
                    Font::STYLE_NORMAL => $resourcesDir.'/verdana.ttf',
                    Font::STYLE_BOLD => $resourcesDir.'/verdanab.ttf',
                ),
            ),
            array(
                array(
                    Font::STYLE_NORMAL => 'courier',
                    Font::STYLE_BOLD => 'courier-bold',
                ),
            ),
        );
    }
    
    /**
     * @test
     */
    public function createGraphicsContext()
    {
        $size = '1:1';
        
        $gc = $this->engine->createGraphicsContext($size);
        
        $this->assertInstanceOf('PHPPdf\Engine\ZF\GraphicsContext', $gc);
        
        $this->assertEquals(array(), $this->zendPdf->pages);
        
        $this->engine->attachGraphicsContext($gc);
        
        $this->assertEquals(array($gc->getPage()), $this->zendPdf->pages);
    }
    
    /**
     * @test
     */
    public function delegateRenderingToZendPdf()
    {
        $content = '123';
        
        $zendPdf = $this->getMockBuilder('Zend_Pdf')
                        ->setMethods(array('render'))
                        ->getMock();

        $zendPdf->expects($this->once())
                ->method('render')
                ->will($this->returnValue($content));
        
        $engine = new Engine($zendPdf);
        
        $this->assertEquals($content, $engine->render());
    }
}