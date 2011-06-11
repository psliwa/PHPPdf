<?php

use PHPPdf\Glyph\TextTransformator;

class TextTransformatorTest extends TestCase
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