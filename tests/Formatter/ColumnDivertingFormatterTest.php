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
        $boundary->setNext($point->translate(0, $yStart))
                 ->setNext($point->translate($container->getWidth(), $yStart))
                 ->setNext($point->translate($container->getWidth(), $yStart + $container->getHeight()))
                 ->setNext($point->translate(0, $yStart + $container->getHeight()))
                 ->close();

        $this->invokeMethod($container, 'setBoundary', array($boundary));
    }

    /**
     * @test
     */
    public function formatColumnsAndSetValidPositionOfContainers()
    {
        $pageHeight = $this->page->getHeight();
        $width = 100;

        $containers = $this->createContainers(array($pageHeight, $pageHeight/2, $pageHeight/3));

        $this->formatter->format($this->column, new Document());

        $this->assertEquals(2, count($this->column->getContainers()));

        $bottomYCoords = array();
        foreach($containers as $container)
        {
            $bottomYCoords[] = $container->getDiagonalPoint()->getY();
        }

        $bottomYCoord = min($bottomYCoords);

        foreach($this->column->getContainers() as $container)
        {
            $child = current($container->getChildren());
            $expectedFirstPoint = $child->getFirstPoint();

            $this->assertEquals($expectedFirstPoint, $container->getFirstPoint());
            $this->assertEquals($bottomYCoord, $container->getDiagonalPoint()->getY());
        }

        //x coord of two containers within the same column is equal
        $this->assertEquals($containers[1]->getFirstPoint()->getX(), $containers[2]->getFirstPoint()->getX());

        $this->assertEquals($pageHeight, $this->column->getHeight());
    }

    private function createContainers(array $heights)
    {
        $width = 100;

        $yStart = 0;

        $containers = array();
        foreach($heights as $height)
        {
            $container = new Container();
            $container->setHeight($height);
            $container->setWidth($width);

            $this->column->add($container);

            $this->injectBoundary($container, $yStart);
            $yStart += $height;

            $containers[] = $container;
        }

        return $containers;
    }

    /**
     * @test
     */
    public function secondRowOfColumnsShouldBeDirectlyUnderFirstRow()
    {
        $pageHeight = $this->page->getHeight();

        $containers = $this->createContainers(array($pageHeight, $pageHeight, $pageHeight));

        $this->formatter->format($this->column, new Document());

        $columns = $this->column->getContainers();

        $this->assertEquals($columns[0]->getDiagonalPoint()->getY(), $columns[2]->getFirstPoint()->getY());

        $this->assertEquals(2*$pageHeight, $this->column->getHeight());
    }
}