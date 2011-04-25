<?php

use PHPPdf\Glyph\ColumnableContainer,
    PHPPdf\Glyph\Container;

class ColumnableContainerTest extends TestCase
{
    const COLUMN_WIDTH = 400;
    const COLUMN_X_COORD = 0;
    const COLUMN_Y_COORD = 400;

    private $column;
    private $container;

    public function setUp()
    {
        $this->container = $this->getMock('PHPPdf\Glyph\Container', array('copy'));
        $this->column = new ColumnableContainer($this->container);

        $this->column->getBoundary()->setNext(self::COLUMN_X_COORD, self::COLUMN_Y_COORD)
                                    ->setNext(self::COLUMN_WIDTH, self::COLUMN_Y_COORD);
        $this->column->setWidth(self::COLUMN_WIDTH);
    }

    /**
     * @test
     * @dataProvider integersProvider
     */
    public function internalContainersCreation($numberOfContainerCreation)
    {
        $containers = array();

        for($i=0; $i<$numberOfContainerCreation; $i++)
        {
            $container = new Container();
            $containers[] = $container;
            $this->container->expects($this->at($i))
                 ->method('copy')
                 ->will($this->returnValue($container));
        }

        for($i=0; $i<$numberOfContainerCreation; $i++)
        {
            $this->column->createNextContainer();
            $this->assertTrue($containers[$i] === $this->column->getCurrentContainer());
        }
        $this->assertEquals($containers, $this->column->getContainers());
    }

    public function integersProvider()
    {
        return array(
            array(1),
            array(4),
        );
    }

    /**
     * @test
     */
    public function firstContainerIsLazyCreatedByDefault()
    {
        $container = new Container();

        $this->container->expects($this->once())
             ->method('copy')
             ->will($this->returnValue($container));

        $this->assertEquals($container, $this->column->getCurrentContainer());
    }

    /**
     * @test
     */
    public function setWidthAfterSettingParent()
    {
        $width = 500;
        $page = $this->getMock('PHPPdf\Glyph\Page', array('getWidth'));
        $page->expects($this->atLeastOnce())
             ->method('getWidth')
             ->will($this->returnValue($width));

        $this->column->setParent($page);

        $expectedWidth = $width / $this->column->getAttribute('number-of-columns') - ($this->column->getAttribute('number-of-columns') - 1)*$this->column->getAttribute('margin-between-columns');
        $this->assertEquals($expectedWidth, $this->column->getWidth());
    }
}