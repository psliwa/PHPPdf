<?php

use PHPPdf\Document;
use PHPPdf\Util\Boundary;
use PHPPdf\Node\Node;
use PHPPdf\Node\Container;
use PHPPdf\Formatter\ContainerDimensionFormatter;

class ContainerDimensionFormatterTest extends TestCase
{
    private $formatter;
    private $objectMother;

    protected function init()
    {
        $this->objectMother = new GenericNodeObjectMother($this);
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

        $this->formatter->format($composeNode, new Document());

        $height = 0;
        foreach($children as $child)
        {
            $height += $child->getHeight();
        }

        $this->assertEquals($height, $composeNode->getHeight());
        $this->assertEquals(200, $composeNode->getWidth());
    }
}