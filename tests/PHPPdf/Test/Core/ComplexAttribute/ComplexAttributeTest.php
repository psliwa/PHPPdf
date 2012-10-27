<?php

namespace PHPPdf\Test\Core\ComplexAttribute;

use PHPPdf\Core\ComplexAttribute\ComplexAttribute;
use PHPPdf\Core\Point;
use PHPPdf\Core\Engine\GraphicsContext;


abstract class ComplexAttributeTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    protected $document;
    
    protected function assertDrawCircle(ComplexAttribute $attribute, $color, $radius, Point $centerPoint, $fillType)
    {        
        $gc = $this->getMock('PHPPdf\Core\Engine\GraphicsContext');
        
        $node = $this->getMockBuilder('PHPPdf\Core\Node\Circle')
                     ->setMethods(array('getGraphicsContext', 'getMiddlePoint'))
                     ->getMock();
        $node->setAttribute('radius', $radius);
        
        $node->expects($this->atLeastOnce())
             ->method('getGraphicsContext')
             ->will($this->returnValue($gc));
        $node->expects($this->atLeastOnce())
             ->method('getMiddlePoint')
             ->will($this->returnValue($centerPoint));
             
        $gc->expects($this->once())
           ->method('drawEllipse')
           ->with($centerPoint->getX(), $centerPoint->getY(), $radius*2, $radius*2, $fillType);           
        
        $attribute->enhance($node, $this->document);        
    }
}