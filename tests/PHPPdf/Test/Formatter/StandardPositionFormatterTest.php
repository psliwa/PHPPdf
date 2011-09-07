<?php

namespace PHPPdf\Test\Formatter;

use PHPPdf\Document,
    PHPPdf\Util\Point,
    PHPPdf\Node\Node,
    PHPPdf\Node\Container,
    PHPPdf\Node\Page,
    PHPPdf\Formatter\StandardPositionFormatter;

class StandardPositionFormatterTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $formatter;

    public function setUp()
    {
        $this->formatter = new StandardPositionFormatter();
    }

    /**
     * @test
     */
    public function nodeWithAutoMarginPositioning()
    {
        $node = new Container(array('width' => 100, 'height' => 100));
        $node->hadAutoMargins(true);
        $node->makeAttributesSnapshot();
        $node->setWidth(110);

        $child = new Container(array('width' => 50, 'height' => 50));
        $node->add($child);
        $page = new Page();
        $page->add($node);

        $node->getBoundary()->setNext($page->getFirstPoint());
        $child->getBoundary()->setNext($page->getFirstPoint());

        foreach(array($node, $child) as $g)
        {
            $this->formatter->format($g, new Document());
        }

        $nodeBoundary = $node->getBoundary();
        $childBoundary = $child->getBoundary();
        $pageBoundary = $page->getBoundary();


        $this->assertEquals($pageBoundary[0]->translate(-5, 0), $nodeBoundary[0]);
        $this->assertEquals($pageBoundary[0]->translate(105, 0), $nodeBoundary[1]);
        $this->assertEquals($pageBoundary[0]->translate(105, 100), $nodeBoundary[2]);
        $this->assertEquals($pageBoundary[0]->translate(-5, 100), $nodeBoundary[3]);
    }
}