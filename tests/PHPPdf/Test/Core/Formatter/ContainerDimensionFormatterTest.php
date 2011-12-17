<?php

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Document;
use PHPPdf\Core\Boundary;
use PHPPdf\Core\Node\Node;
use PHPPdf\Core\Node\Container;
use PHPPdf\Core\Formatter\ContainerDimensionFormatter;
use PHPPdf\ObjectMother\NodeObjectMother;

class ContainerDimensionFormatterTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $formatter;
    private $objectMother;

    protected function init()
    {
        $this->objectMother = new NodeObjectMother($this);
    }
    
    public function setUp()
    {
        $this->formatter = new ContainerDimensionFormatter();
    }

    /**
     * @test
     */
    public function nodeFormatter()
    {
        $composeNode = new Container();
        $composeNode->setWidth(140);
        $children = array();
        $children[] = $this->objectMother->getNodeStub(0, 500, 100, 200);
        $children[] = $this->objectMother->getNodeStub(0, 300, 200, 200);

        foreach($children as $child)
        {
            $composeNode->add($child);
        }
        
        $this->formatter->format($composeNode, $this->createDocumentStub());

        $height = 0;
        foreach($children as $child)
        {
            $height += $child->getHeight();
        }

        $this->assertEquals($height, $composeNode->getHeight());
        $this->assertEquals(200, $composeNode->getWidth());
    }
}