<?php

namespace PHPPdf\Test\Core\Engine\Imagine;

use PHPPdf\Core\Engine\Imagine\Font;

use Imagine\Image\Box;
use PHPPdf\Core\Engine\Imagine\Engine;
use PHPPdf\PHPUnit\Framework\TestCase;

class EngineTest extends TestCase
{
    private $engine;
    private $imagine;
    
    public function setUp()
    {
        $this->imagine = $this->getMock('Imagine\Image\ImagineInterface');
        $this->engine = new Engine($this->imagine, 'png');
    }
    
    /**
     * @test
     */
    public function createGraphicsContext()
    {
        $image = $this->getMock('Imagine\Image\ImageInterface');
        
        $size = '100:200';
        
        list($width, $height) = explode(':', $size);
        
        $box = new Box($width, $height);
        
        $this->imagine->expects($this->once())
                      ->method('create')
                      ->with($box)
                      ->will($this->returnValue($image));
                      
        $image->expects($this->atLeastOnce())
              ->method('getSize')
              ->will($this->returnValue($box));
                      
        $gc = $this->engine->createGraphicsContext($size, 'utf-8');
        
        $this->assertInstanceOf('PHPPdf\Core\Engine\Imagine\GraphicsContext', $gc);
        
        $this->assertEquals($width, $gc->getWidth());
        $this->assertEquals($height, $gc->getHeight());
    }
    
    /**
     * @test
     */
    public function createImage()
    {
        $imagineImage = $this->getMock('Imagine\Image\ImageInterface');
        
        $path = 'some/image/path';
        
        $this->imagine->expects($this->once())
                      ->method('open')
                      ->with($path)
                      ->will($this->returnValue($imagineImage));
                      
        $image = $this->engine->createImage($path);
        
        $this->assertInstanceOf('PHPPdf\Core\Engine\Imagine\Image', $image);
        $this->assertEquals($imagineImage, $image->getWrappedImage());
    }
    
    /**
     * @test
     * @expectedException PHPPdf\Exception\InvalidResourceException
     */
    public function wrapExceptionOnImageCreationFailure()
    {
        $path = 'path';
        
        $this->imagine->expects($this->once())
                      ->method('open')
                      ->with($path)
                      ->will($this->throwException(new \Imagine\Exception\InvalidArgumentException()));
                      
        $this->engine->createImage($path);
    }
    
    /**
     * @test
     */
    public function createFont()
    {
        $fontData = array(
            Font::STYLE_NORMAL => TEST_RESOURCES_DIR.'/font-judson/normal.ttf',
        );
        
        $size = 123;
        $imagineFont = $this->getMock('Imagine\Image\FontInterface');
        
        $this->imagine->expects($this->once())
                      ->method('font')
                      ->with($fontData[Font::STYLE_NORMAL], $size, $this->anything())
                      ->will($this->returnValue($imagineFont));
        
        $font = $this->engine->createFont($fontData);
        
        $this->assertInstanceOf('PHPPdf\Core\Engine\Imagine\Font', $font);
        $this->assertEquals($imagineFont, $font->getWrappedFont('#000000', $size));
    }
    
    /**
     * @test
     */
    public function render()
    {
        $expectedContents = array(
            'some content',
            '1234',
            'fasdfsdaf',
        );

        $this->setGraphicsContextsWithRenderExceptation($expectedContents);
        
        $actualContents = $this->engine->render();
        
        $this->assertEquals($expectedContents, $actualContents);
    }
    
    private function setGraphicsContextsWithRenderExceptation(array $expectedContents)
    {
        $gcs = array();
        
        foreach($expectedContents as $content)
        {
            $gc = $this->getMockBuilder('PHPPdf\Core\Engine\Imagine\GraphicsContext')
                       ->setMethods(array('render', 'commit'))
                       ->disableOriginalConstructor()
                       ->getMock();
                       
            $gc->expects($this->once())
               ->method('commit')
               ->id(1);
            $gc->expects($this->once())
               ->method('render')
               ->after(1)
               ->will($this->returnValue($content));
               
            $this->engine->attachGraphicsContext($gc);
        }
    }
    
    /**
     * @test
     */
    public function loadEngineSuccess()
    {
        $path = 'some.png';
        
        $image = $this->getMock('Imagine\Image\ImageInterface');
        
        $this->imagine->expects($this->once())
                      ->method('open')
                      ->with($path)
                      ->will($this->returnValue($image));
        
        $engine = $this->engine->loadEngine($path, 'utf-8');
        
        $this->assertEquals(1, count($engine->getAttachedGraphicsContexts()));
    }
    
    /**
     * @test
     * @expectedException PHPPdf\Exception\InvalidResourceException
     */
    public function loadEngineFailure()
    {
        $path = 'some.png';
        
        $this->imagine->expects($this->once())
                      ->method('open')
                      ->with($path)
                      ->will($this->throwException(new \Imagine\Exception\RuntimeException()));

       $this->engine->loadEngine($path, 'utf-8');
    }
}