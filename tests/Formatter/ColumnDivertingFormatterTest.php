<?php

use PHPPdf\Glyph\ColumnableContainer,
    PHPPdf\Glyph\Container,
    PHPPdf\Glyph\Page,
    PHPPdf\Util\Boundary,
    PHPPdf\Document,
    PHPPdf\Formatter\ColumnDivertingFormatter;

class ColumnDivertingFormatterTest extends TestCase
{
    private $page;
    private $column;
    private $formatter;

    public function setUp()
    {
        $this->page = new Page();
        $this->column = new ColumnableContainer(new Container());

        $this->column->setHeight($this->page->getHeight()*1.5);
        $this->column->setWidth($this->page->getWidth()/2);

        $this->page->add($this->column);
        $this->formatter = new ColumnDivertingFormatter();

        $this->injectBoundary($this->column);
    }

    private function injectBoundary(Container $container, $yStart = 0)
    {
        $parent = $container->getParent();
        $point = $parent->getFirstPoint();

        $boundary = new Boundary();
        $boundary->setNext($point)
                 ->setNext($point->translate($container->getWidth(), $yStart))
                 ->setNext($point->translate($container->getWidth(), $yStart + $container->getHeight()))
                 ->setNext($point->translate(0, $yStart + $container->getHeight()))
                 ->close();

        $this->invokeMethod($container, 'setBoundary', array($boundary));
    }

    /**
     * @test
     */
    public function splitContainersIntoColumns()
    {
        $pageHeight = $this->page->getHeight();
        $width = 100;

        $yStart = 0;
        foreach(array($pageHeight, $pageHeight/2) as $height)
        {
            $container = new Container();
            $container->setHeight($height);
            $container->setWidth($width);

            $this->column->add($container);
            
            $this->injectBoundary($container, $yStart);
            $yStart += $height;
        }

        $this->formatter->format($this->column, new Document());

        $this->assertEquals(2, count($this->column->getContainers()));
    }
}