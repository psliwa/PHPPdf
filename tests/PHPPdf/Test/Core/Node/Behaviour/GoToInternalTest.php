<?php

namespace PHPPdf\Test\Core\Node\Behaviour;

use PHPPdf\Core\Point,
    PHPPdf\ObjectMother\NodeObjectMother,
    PHPPdf\Core\Node\Container,
    PHPPdf\Core\Node\Behaviour\GoToInternal;

class GoToInternalTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $objectMother;
    
    public function init()
    {
        $this->objectMother = new NodeObjectMother($this);
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
        
        $destination = $this->getMockBuilder('PHPPdf\Core\Node\Container')
                            ->setMethods(array('getFirstPoint', 'getGraphicsContext', 'getNode'))
                            ->getMock();
                            
        $destination->expects($this->atLeastOnce())
                    ->method('getFirstPoint')
                    ->will($this->returnValue($firstPoint));
                    
        $destination->expects($this->atLeastOnce())
                    ->method('getNode')
                    ->will($this->returnValue($destination));
                    
        $gc = $this->getMockBuilder('PHPPdf\Core\Engine\GraphicsContext')
                   ->getMock();
         
        $gc->expects($this->once())
           ->method('goToAction')
           ->with($gc, $x, $y, $x+$width, $y-$height, $firstPoint->getY());
           
        $destination->expects($this->atLeastOnce())
                    ->method('getGraphicsContext')
                    ->will($this->returnValue($gc));
                            
        $nodeStub = $this->getNodeStub($x, $y, $width, $height);
        
        $behaviour =  new GoToInternal($destination);
        
        $behaviour->attach($gc, $nodeStub);
    }
    
    private function getNodeStub($x, $y, $width, $height)
    {
        $boundary = $this->objectMother->getBoundaryStub($x, $y, $width, $height);
        
        $node = new Container();
        $this->invokeMethod($node, 'setBoundary', array($boundary));
        
        return $node;
    }
    
    /**
     * @test
     * @expectedException PHPPdf\Exception\RuntimeException
     */
    public function throwExceptionIfDestinationIsEmpty()
    {
        $destination = $this->getMockBuilder('PHPPdf\Core\Node\NodeAware')
                            ->getMock();
        $destination->expects($this->once())
                    ->method('getNode')
                    ->will($this->returnValue(null));

        $gc = $this->getMockBuilder('PHPPdf\Core\Engine\GraphicsContext')
                   ->getMock();      
                    
        $behaviour =  new GoToInternal($destination);
        
        $behaviour->attach($gc, new Container());
    }
}