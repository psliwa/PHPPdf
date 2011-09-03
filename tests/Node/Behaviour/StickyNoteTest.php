<?php

namespace PHPPdf\Test\Node\Behaviour;

use PHPPdf\Node\Behaviour\StickyNote;

use PHPPdf\Node\Container;

class StickyNoteTest extends \TestCase
{
    private $objectMother;
    
    public function init()
    {
        $this->objectMother = new \GenericNodeObjectMother($this);
    }
    
    /**
     * @test
     */
    public function attachNote()
    {
        $x = 10;
        $y = 200;
        $width = 100;
        $height = 200;
        
        $node = $this->getNodeStub($x, $y, $width, $height);
        
        $gc = $this->getMockBuilder('PHPPdf\Engine\GraphicsContext')
                   ->getMock();
        
       $text = 'some text';

        $gc->expects($this->once())
           ->method('attachStickyNote')
           ->with($x, $y, $x+$width, $y-$height, $text);
           
        $stickyNote = new StickyNote($text);
        
        $stickyNote->attach($gc, $node);        
    }
    
    private function getNodeStub($x, $y, $width, $height)
    {
        $boundary = $this->objectMother->getBoundaryStub($x, $y, $width, $height);
        
        $node = new Container();
        $this->invokeMethod($node, 'setBoundary', array($boundary));
        
        return $node;
    }
}