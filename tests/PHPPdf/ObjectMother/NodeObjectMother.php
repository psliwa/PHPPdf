<?php

namespace PHPPdf\ObjectMother;

use PHPPdf\Core\Node\Container;
use PHPPdf\Core\Engine\GraphicsContext;

class NodeObjectMother
{
    private $test;

    public function __construct(\PHPUnit_Framework_TestCase $test)
    {
        $this->test = $test;
    }

    public function getPageMock($x, $y)
    {
        $gcMock = $this->getMockBuilder('PHPPdf\Core\Engine\GraphicsContext')
        			   ->getMock();
        $gcMock->expects($this->test->once())
                 ->method('drawPolygon')
                 ->with($x, $y, GraphicsContext::SHAPE_DRAW_STROKE);

        $pageMock = $this->getEmptyPageMock($gcMock);

        return $pageMock;
    }

    public function getEmptyPageMock($graphicsContext)
    {
        $pageMock = $this->test->getMock('PHPPdf\Core\Node\Page', array('getGraphicsContext'));

        $pageMock->expects($this->test->atLeastOnce())
                 ->method('getGraphicsContext')
                 ->will($this->test->returnValue($graphicsContext));

        return $pageMock;
    }

    public function getNodeMock($x, $y, $width, $height, $gc = null)
    {
        $boundaryMock = $this->getBoundaryStub($x, $y, $width, $height);

        $nodeMock = $this->test->getMock('PHPPdf\Core\Node\Node', array('getBoundary', 'getWidth', 'getHeight', 'getGraphicsContext'));

        $nodeMock->expects($this->test->atLeastOnce())
                  ->method('getBoundary')
                  ->will($this->test->returnValue($boundaryMock));

        $nodeMock->expects($this->test->any())
                  ->method('getWidth')
                  ->will($this->test->returnValue($width));

        $nodeMock->expects($this->test->any())
                  ->method('getHeight')
                  ->will($this->test->returnValue($height));
                  
        if($gc)
        {
            $nodeMock->expects($this->test->atLeastOnce())
                      ->method('getGraphicsContext')
                      ->will($this->test->returnValue($gc));
        }

        return $nodeMock;
    }

    public function getBoundaryStub($x, $y, $width, $height)
    {
        $boundary = new \PHPPdf\Core\Boundary();

        $boundary->setNext($x, $y)
                 ->setNext($x+$width, $y)
                 ->setNext($x+$width, $y-$height)
                 ->setNext($x, $y-$height)
                 ->close();

        return $boundary;
    }
    
    public function getNodeStub($x, $y, $width, $height)
    {
        $boundary = $this->getBoundaryStub($x, $y, $width, $height);
        $node = new Container();
        
        $this->test->invokeMethod($node, 'setBoundary', array($boundary));
        
        $node->setWidth($width);
        $node->setHeight($height);
        
        return $node;
    }
}