<?php

namespace PHPPdf\Test\Core\Engine\ZF;

use PHPPdf\Core\Engine\Font;

use PHPPdf\Core\Engine\ZF\Engine;

class EngineTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $engine;
    private $zendPdf;
    
    public function setUp()
    {
        if(!class_exists('ZendPdf\PdfDocument', true))
        {
            $this->fail('Zend Framework 2 library is missing. You have to download dependencies, for example by using "vendors.php" file.');
        }
        
        $this->zendPdf = new \ZendPdf\PdfDocument();
        $this->engine = new Engine($this->zendPdf);
    }
    
    /**
     * @test
     */
    public function createImage()
    {
        $image = $this->engine->createImage(TEST_RESOURCES_DIR.'/domek.png');
        
        $this->assertInstanceOf('PHPPdf\Core\Engine\ZF\Image', $image);
    }
    
    /**
     * @test
     * @dataProvider fontProvider
     */
    public function createFont($fontData)
    {
        $font = $this->engine->createFont($fontData);
        
        $this->assertInstanceOf('PHPPdf\Core\Engine\ZF\Font', $font);
        
        foreach($fontData as $style => $data)
        {
            $this->assertTrue($font->hasStyle($style));
        }
    }
    
    public function fontProvider()
    {
        $resourcesDir = TEST_RESOURCES_DIR.'/resources';
        return array(
            array(
                array(
                    Font::STYLE_NORMAL => $resourcesDir.'/font-judson/normal.ttf',
                    Font::STYLE_BOLD => $resourcesDir.'/font-judson/bold.ttf',
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
        
        $gc = $this->engine->createGraphicsContext($size, 'utf-8');
        
        $this->assertInstanceOf('PHPPdf\Core\Engine\ZF\GraphicsContext', $gc);
        
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
        
        $zendPdf = $this->getMockBuilder('ZendPdf\PdfDocument')
                        ->setMethods(array('render'))
                        ->getMock();

        $zendPdf->expects($this->once())
                ->method('render')
                ->will($this->returnValue($content));
        
        $engine = new Engine($zendPdf);
        
        $this->assertEquals($content, $engine->render());
    }
    
    /**
     * @test
     */    
    public function successfullEngineLoading()
    {
        $file = TEST_RESOURCES_DIR.'/test.pdf';
        
        $engine = new Engine();
        
        $loadedEngine = $engine->loadEngine($file, 'utf-8');
        
        $this->assertFalse($loadedEngine === $engine);
        $this->assertInstanceOf('PHPPdf\Core\Engine\ZF\Engine', $loadedEngine);
        $this->assertEquals(2, count($loadedEngine->getAttachedGraphicsContexts()));
    }

    /**
     * @test
     * @expectedException PHPPdf\Exception\InvalidResourceException
     */
    public function throwExceptionIfFileIsInvalidWhileEngineLoading()
    {
        $file = 'some/invalid/filename.pdf';
        
        $engine = new Engine();
        
        $engine->loadEngine($file, 'utf-8');
    }
    
    /**
     * @test
     * @dataProvider metadataProvider
     */
    public function setMetadataValues($name, $value, $shouldBeSet, $expectedValue = null)
    {
        $zendPdf = new \ZendPdf\PdfDocument();
        $engine = new Engine($zendPdf);
        
        $engine->setMetadataValue($name, $value);
        
        if($shouldBeSet)
        {
            $this->assertEquals($expectedValue, $zendPdf->properties[$name]);
        }
        else
        {
            $this->assertFalse(isset($zendPdf->properties[$name]));
        }
    }
    
    public function metadataProvider()
    {
        return array(
            array('Trapped', 'true', true, true),
            array('Trapped', 'false', true, false),
            array('Trapped', true, true, true),
            array('Trapped', 'null', true, null),
            array('Author', 'Author', true, 'Author'),
            array('InvalidProperty', 'value', false),
        );
    }
    
    /**
     * @test
     */
    public function delegateConvertUnitInvocationToConverter()
    {
        $converter = $this->getMock('PHPPdf\Core\UnitConverter');
        
        $engine = new Engine(null, $converter);
        
        $value = 123;
        $unit = 'abc';
        $percentage = '100%';
        $result = 321;
        
        $converter->expects($this->at(0))
                  ->method('convertUnit')
                  ->with($value, $unit)
                  ->will($this->returnValue($result));
                  
        $converter->expects($this->at(1))
                  ->method('convertPercentageValue')
                  ->with($percentage, $value)
                  ->will($this->returnValue($result));
                  
        $this->assertEquals($result, $engine->convertUnit($value, $unit));        
        $this->assertEquals($result, $engine->convertPercentageValue($percentage, $value));
    }
}