<?php

namespace PHPPdf\Test\Glyph\Behaviour;

use PHPPdf\Util\Point,
    PHPPdf\Glyph\Container,
    PHPPdf\Glyph\Behaviour\GoToInternal;

class GoToInternalTest extends \TestCase
{
    private $objectMother;
    
    public function init()
    {
        $this->objectMother = new \GenericGlyphObjectMother($this);
    }
    
    /**
     * @test
     */
    public function attachGoToActionToGraphicsContext()
    {
        $x = 0;
        $y = 500;
        $width = 100;
        $height = 100;
        
        $firstPoint = Point::getInstance(400, 300);
        
        $destination = $this->getMockBuilder('PHPPdf\Glyph\Container')
                            ->setMethods(array('getFirstPoint', 'getGraphicsContext', 'getGlyph'))
                            ->getMock();
                            
        $destination->expects($this->atLeastOnce())
                    ->method('getFirstPoint')
                    ->will($this->returnValue($firstPoint));
                    
        $destination->expects($this->atLeastOnce())
                    ->method('getGlyph')
                    ->will($this->returnValue($destination));
                    
        $gc = $this->getMockBuilder('PHPPdf\Engine\GraphicsContext')
                   ->getMock();
         
        $gc->expects($this->once())
           ->method('goToAction')
           ->with($gc, $x, $y, $x+$width, $y-$height, $firstPoint->getY());
           
        $destination->expects($this->atLeastOnce())
                    ->method('getGraphicsContext')
                    ->will($this->returnValue($gc));
                            
        $glyphStub = $this->getGlyphStub($x, $y, $width, $height);
        
        $behaviour =  new GoToInternal($destination);
        
        $behaviour->attach($gc, $glyphStub);
    }
    
    private function getGlyphStub($x, $y, $width, $height)
    {
        $boundary = $this->objectMother->getBoundaryStub($x, $y, $width, $height);
        
        $glyph = new Container();
        $this->invokeMethod($glyph, 'setBoundary', array($boundary));
        
        return $glyph;
    }
    
    /**
     * @test
     * @expectedException PHPPdf\Exception\Exception
     */
    public function throwExceptionIfDestinationIsEmpty()
    {
        $destination = $this->getMockBuilder('PHPPdf\Glyph\GlyphAware')
                            ->getMock();
        $destination->expects($this->once())
                    ->method('getGlyph')
                    ->will($this->returnValue(null));

        $gc = $this->getMockBuilder('PHPPdf\Engine\GraphicsContext')
                   ->getMock();      
                    
        $behaviour =  new GoToInternal($destination);
        
        $behaviour->attach($gc, new Container());
    }
}