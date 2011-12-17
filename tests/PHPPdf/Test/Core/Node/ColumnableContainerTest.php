<?php

namespace PHPPdf\Test\Core\Node;

use PHPPdf\Core\Document;
use PHPPdf\Core\Node\ColumnableContainer,
    PHPPdf\Core\Node\Container;

class ColumnableContainerTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    const COLUMN_WIDTH = 400;
    const COLUMN_X_COORD = 0;
    const COLUMN_Y_COORD = 400;

    private $column;

    public function setUp()
    {
        $this->column = new ColumnableContainer();

        $this->column->getBoundary()->setNext(self::COLUMN_X_COORD, self::COLUMN_Y_COORD)
                                    ->setNext(self::COLUMN_WIDTH, self::COLUMN_Y_COORD);
        $this->column->setWidth(self::COLUMN_WIDTH);
    }

    /**
     * @test
     */
    public function setWidthOnPreFormat()
    {
        $width = 500;
        $page = $this->getMock('PHPPdf\Core\Node\Page', array('getWidth'));
        $page->expects($this->atLeastOnce())
             ->method('getWidth')
             ->will($this->returnValue($width));
             
        $this->column->setParent($page);

        $this->column->format($this->createDocumentStub());

        $expectedWidth = 245;
        $this->assertEquals($expectedWidth, $this->column->getWidth());
    }
}