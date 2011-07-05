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
        $this->column = new ColumnableContainer();

        $this->column->setHeight($this->page->getHeight()*1.5);
        $this->column->setWidth($this->page->getWidth()/2);

        $this->formatter = new ColumnDivertingFormatter();
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
        $this->page->add($this->column);

        $pageHeight = $this->page->getHeight();
        $width = 100;

        $containersHeight = array($pageHeight, $pageHeight/2, $pageHeight/3);
        $this->column->setHeight(array_sum($containersHeight));
        $this->injectBoundary($this->column);
        
        $containers = $this->createContainers($containersHeight);

        $this->formatter->format($this->column, new Document());

        $this->assertEquals(2, count($this->column->getChildren()));

        foreach($this->column->getChildren() as $container)
        {
            $children = $container->getChildren();
            $firstChild = current($children);
            $expectedFirstPoint = $firstChild->getFirstPoint();
            
            $lastIndex = count($children) - 1;
            $lastChild = $children[$lastIndex];
            $expectedDiagonalYCoord = min($lastChild->getDiagonalPoint()->getY(), $firstChild->getDiagonalPoint()->getY());
            $actualDiagonalYCoord = $container->getDiagonalPoint()->getY();

            $this->assertEquals($expectedFirstPoint, $container->getFirstPoint());
            $this->assertEquals($expectedDiagonalYCoord, $actualDiagonalYCoord);
        }

        $this->assertEquals($containers[1]->getFirstPoint()->getX(), $containers[2]->getFirstPoint()->getX(), 'x coord of two containers within the same column is not equal');

        $this->assertEquals($pageHeight, $this->column->getHeight());
    }

    private function createContainers(array $heights, $parent = null)
    {
        $parent = $parent ? $parent : $this->column;
        $width = 100;

        $yStart = 0;

        $containers = array();
        foreach($heights as $height)
        {
            $container = new Container();
            $container->setHeight($height);
            $container->setWidth($width);

            $parent->add($container);

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
        $this->page->add($this->column);

        $pageHeight = $this->page->getHeight();

        $containersHeights = array($pageHeight, $pageHeight, $pageHeight);
        $this->column->setHeight(array_sum($containersHeights));
        $this->injectBoundary($this->column);       
        
        $containers = $this->createContainers($containersHeights);

        $this->formatter->format($this->column, new Document());

        $columns = $this->column->getChildren();

        $this->assertEquals($columns[0]->getDiagonalPoint()->getY(), $columns[2]->getFirstPoint()->getY());

        $this->assertEquals(2*$pageHeight, $this->column->getHeight());
    }

    /**
     * @test
     */
    public function containerInSecondColumnHasTheSameYCoordAsFirstContainer()
    {
        $stubs = $this->createContainers(array(20), $this->page);

        $pageHeight = $this->page->getHeight();

        $this->page->add($this->column);
        $this->injectBoundary($this->column, 20);
        $containers = $this->createContainers(array($pageHeight*1.5));

        $this->formatter->format($this->column, new Document());

        $columns = $this->column->getChildren();

        $this->assertEquals($stubs[0]->getDiagonalPoint()->getY(), $columns[0]->getFirstPoint()->getY());
        $this->assertEquals($columns[0]->getFirstPoint()->getY(), $columns[1]->getFirstPoint()->getY());

        foreach($this->column->getChildren() as $container)
        {
            foreach($container->getChildren() as $child)
            {
                $this->assertEquals($container->getFirstPoint()->getY(), $child->getFirstPoint()->getY());
            }
        }
    }
    
    /**
     * @test
     */
    public function calculateCorrectBreakPointWhenColumnStartsInMiddleOfPage()
    {
        $pageHeight = $this->page->getHeight();
        
        $heightOfFirstContainer = 20;
               
        list($fistContainer, $columnableContainerChild) = $this->createContainers(array($heightOfFirstContainer, 4*$pageHeight - 2*$heightOfFirstContainer), $this->page);
        $this->column->add($columnableContainerChild);
        $this->page->add($this->column);
        
        $this->column->setHeight($columnableContainerChild->getHeight());
        $this->injectBoundary($this->column, 20);
        
        $this->formatter->format($this->column, new Document());
        
        $this->assertEquals(4, count($this->column->getChildren()));
        
        foreach($this->column->getChildren() as $container)
        {
            $this->assertEquals(0, $container->getDiagonalPoint()->getY() % $pageHeight, 'y coord of one container\'s bottom is not equal to page bottom');
        }
    }
}