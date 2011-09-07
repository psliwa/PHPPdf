<?php

namespace PHPPdf\Test\Node\Behaviour;

use PHPPdf\Node\Container;
use PHPPdf\ObjectMother\NodeObjectMother;
use PHPPdf\Node\Behaviour\Bookmark;

class BookmarkTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $objectMother;
    
    public function init()
    {
        $this->objectMother = new NodeObjectMother($this);
    }
    
    /**
     * @test
     */
    public function attachBookmarkSingleToGraphicsContexts()
    {
        $name = 'some name';
        $top = 50;
        $bookmark = new Bookmark($name);
        
        $node = $this->getNodeStub(0, $top, 100, 100);
        
        $gc = $this->getMockBuilder('PHPPdf\Engine\GraphicsContext')
                   ->getMock();
        $gc->expects($this->once())
           ->method('addBookmark')
           ->with($bookmark->getUniqueId(), $name, $top);
           
        $bookmark->attach($gc, $node);  
        
        //one bookmark may by attached only once
        $bookmark->attach($gc, $node);        
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
     */
    public function attachNestedBookmarks()
    {
        $parentBookmark = new Bookmark('some name 1');
        
        $parent = $this->getNodeStub(0, 100, 100, 100);
        $parent->addBehaviour($parentBookmark);
        
        $bookmark = new Bookmark('some name 2');
        $node = $this->getNodeStub(0, 50, 100, 50);
        
        $parent->add($node);
        
        $gc = $this->getMockBuilder('PHPPdf\Engine\GraphicsContext')
                   ->getMock();
        $gc->expects($this->once())
           ->method('addBookmark')
           ->with($bookmark->getUniqueId(), $this->anything(), $this->anything(), $parentBookmark->getUniqueId());
           
        $bookmark->attach($gc, $node);  
    }
}