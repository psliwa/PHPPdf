<?php

namespace PHPPdf\Test\Core\Node\Behaviour;

use PHPPdf\Core\Node\Container;
use PHPPdf\ObjectMother\NodeObjectMother;
use PHPPdf\Core\Node\Behaviour\GoToUrl;

class GoToUriTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $objectMother;
    
    public function init()
    {
        $this->objectMother = new NodeObjectMother($this);
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
        
        $nodeStub = $this->getNodeStub($x, $y, $width, $height);
        
        $gc = $this->getMockBuilder('PHPPdf\Core\Engine\GraphicsContext')
                   ->getMock();
         
        $gc->expects($this->once())
           ->method('uriAction')
           ->with($x, $y, $x+$width, $y-$height, $uri);
           
        $behaviour =  new GoToUrl($uri);
        
        $behaviour->attach($gc, $nodeStub);
    }
    
    private function getNodeStub($x, $y, $width, $height)
    {
        $boundary = $this->objectMother->getBoundaryStub($x, $y, $width, $height);
        
        $node = new Container();
        $this->invokeMethod($node, 'setBoundary', array($boundary));
        
        return $node;
    }
}