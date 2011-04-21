<?php

use PHPPdf\Glyph\ColumnableContainer,
    PHPPdf\Glyph\Container;

class ColumnableContainerTest extends TestCase
{
    private $column;
    private $container;

    public function setUp()
    {
        $this->container = $this->getMock('PHPPdf\Glyph\Container', array('copy'));
        $this->column = new ColumnableContainer($this->container);
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
}