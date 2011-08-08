<?php

namespace PHPPdf\Test\Glyph\Behaviour;

use PHPPdf\Glyph\Container;

use PHPPdf\Glyph\Behaviour\GoToUrl;

class GoToUriTest extends \TestCase
{
    private $objectMother;
    
    public function init()
    {
        $this->objectMother = new \GenericGlyphObjectMother($this);
    }

    /**
     * @test
     */
    public function attachGoToUrlActionToGraphicsContext()
    {
        $x = 10;
        $y = 200;
        $width = 50;
        $height = 20;
        
        $uri = 'http://google.com';
        
        $glyphStub = $this->getGlyphStub($x, $y, $width, $height);
        
        $gc = $this->getMockBuilder('PHPPdf\Engine\GraphicsContext')
                   ->getMock();
         
        $gc->expects($this->once())
           ->method('uriAction')
           ->with($x, $y, $x+$width, $y-$height, $uri);
           
        $behaviour =  new GoToUrl($uri);
        
        $behaviour->attach($gc, $glyphStub);
    }
    
    private function getGlyphStub($x, $y, $width, $height)
    {
        $boundary = $this->objectMother->getBoundaryStub($x, $y, $width, $height);
        
        $glyph = new Container();
        $this->invokeMethod($glyph, 'setBoundary', array($boundary));
        
        return $glyph;
    }
}