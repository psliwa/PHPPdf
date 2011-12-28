<?php

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\ObjectMother\NodeObjectMother;
use PHPPdf\Core\Node\Page;
use PHPPdf\Core\Formatter\ElasticPageFormatter;
use PHPPdf\PHPUnit\Framework\TestCase;

class ElasticPageFormatterTest extends TestCase
{
    private $formatter;
    private $document;
    private $nodeObjectMother;
    
    public function setUp()
    {
        $this->formatter = new ElasticPageFormatter();
        $this->document = $this->createDocumentStub();
                               
        $this->nodeObjectMother = new NodeObjectMother($this);
    }
    
    /**
     * @test
     * @dataProvider correctHeightOfPageProvider
     */
    public function correctHeightOfPage($originalHeight, array $childrenDiagonalYCoord)
    {
        $page = new Page(array('page-size' => '500:'.$originalHeight));
        
        foreach($childrenDiagonalYCoord as $yCoord)
        {
            $node = $this->nodeObjectMother->getNodeStub(0, $page->getHeight(), 100, $page->getHeight() - $yCoord);
            $page->add($node);
        }
        
        $minYCoord = $childrenDiagonalYCoord ? min($childrenDiagonalYCoord) : $originalHeight;
        
        $this->formatter->format($page, $this->document);
        
        $expectedHeight = $originalHeight - $minYCoord;
        $translation = $originalHeight - $expectedHeight;
        
        $this->assertEquals($expectedHeight, $page->getRealHeight());
        
        foreach($page->getChildren() as $i => $child)
        {
            $expectedDiagonalYCoord = $childrenDiagonalYCoord[$i] - $translation;
            
            $actualDiagonalYCoord = $child->getDiagonalPoint()->getY();
            
            $this->assertEquals($expectedDiagonalYCoord, $actualDiagonalYCoord);
        }
    }
    
    public function correctHeightOfPageProvider()
    {
        return array(
            array(500, array(300, 400)),
            array(500, array(-300, -200)),
            array(500, array()),
        );
    }
}