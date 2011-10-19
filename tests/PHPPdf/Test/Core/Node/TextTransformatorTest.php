<?php

namespace PHPPdf\Test\Core\Node;

use PHPPdf\Core\Node\TextTransformator;

class TextTransformatorTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $transformator;
    
    public function setUp()
    {
        $this->transformator = new TextTransformator();
    }
    
    /**
     * @test
     */    
    public function replaceReplacements()
    {
        $this->transformator->setReplacements(array('a' => 'b'));
        
        $this->assertEquals('bc', $this->transformator->transform('ac'));
    }
}