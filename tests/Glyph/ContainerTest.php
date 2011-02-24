<?php

use PHPPdf\Document;
use PHPPdf\Glyph\Container;
use PHPPdf\Glyph\AbstractGlyph;

class ContainerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPPdf\Glyph\Container
     */
    private $glyph;

    public function setUp()
    {
        $this->glyph = new Container();
    }

    /**
     * @test
     */
    public function split()
    {
        $this->glyph->setWidth(350)
                    ->setHeight(300);

        $boundary = $this->glyph->getBoundary();
        $boundary->setNext(50, 600)
                 ->setNext(400, 600)
                 ->setNext(400, 300)
                 ->setNext(50, 300)
                 ->close();

        $child1 = new Container();
        $child1->setWidth(350)
               ->setHeight(100);
        $boundary = $child1->getBoundary();
        $boundary->setNext(50, 600)
                 ->setNext(400, 600)
                 ->setNext(400, 500)
                 ->setNext(50, 500)
                 ->close();

        $this->glyph->add($child1);

        $child2 = $child1->copy();
        foreach($boundary as $point)
        {
            $child2->getBoundary()->setNext($point);
        }
        $child2->translate(0, 200);
        $this->glyph->add($child2);

        $result = $this->glyph->split(250);

        $this->assertEquals(250, $this->glyph->getHeight());
        $children = $this->glyph->getChildren();
        $this->assertEquals(2, count($children));
        $this->assertEquals(100, $children[0]->getHeight());
        $this->assertEquals(50, $children[1]->getHeight());
        $this->assertEquals(array(400, 350), $children[1]->getEndDrawingPoint());

        $this->assertEquals(50, $result->getHeight());
        $children = $result->getChildren();
        $this->assertEquals(1, count($children));
        $this->assertEquals(50, $children[0]->getHeight());
        $this->assertEquals(array(50, 350), $children[0]->getStartDrawingPoint());
        $this->assertEquals(array(400, 300), $children[0]->getEndDrawingPoint());
    }
}