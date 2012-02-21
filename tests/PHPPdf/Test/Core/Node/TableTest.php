<?php

namespace PHPPdf\Test\Core\Node;

use PHPPdf\Core\PdfUnitConverter;

use PHPPdf\Core\Node\Table\Cell;
use PHPPdf\Core\Node\Table;
use PHPPdf\Core\Boundary;
use PHPPdf\Core\Node as Nodes;
use PHPPdf\ObjectMother\TableObjectMother;

class TableTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $table = null;
    private $objectMother;

    public function init()
    {
        $this->objectMother = new TableObjectMother($this);
    }

    public function setUp()
    {
        $this->table = new Table(array(), new PdfUnitConverter());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function addingInvalidChild()
    {
        $node = new Nodes\Container();
        $this->table->add($node);
    }

    /**
     * @test
     */
    public function addingValidChild()
    {
        $node = new Nodes\Table\Row();
        $this->table->add($node);

        $this->assertTrue(count($this->table->getChildren()) > 0);
    }

    /**
     * @test
     */
    public function rowsAndCellsAttributes()
    {
        $height = 40;
        $this->table->setAttribute('row-height', $height);
        
        $this->assertEquals($height, $this->table->getAttribute('row-height'));
    }
    
    /**
     * @test
     */
    public function breakAt()
    {
        $numberOfRows = 10;
        $heightOfRow = 50;
        $tableHeight = 500;

        $boundary = $this->table->getBoundary();
        $boundary->setNext(0, $tableHeight)
                 ->setNext(200, $tableHeight)
                 ->setNext(200, 0)
                 ->setNext(0, 0)
                 ->close();

        $this->table->setHeight(500)->setWidth(200);

        $start = 500;
        $pointOfBreaking = 220;
        $reversePointOfBreaking = $start - $pointOfBreaking;
        $rowBreakOccurs = false;
        for($i=0; $i<$numberOfRows; $i++)
        {
            $end = $start-$heightOfRow;
            $break = $reversePointOfBreaking < $start && $reversePointOfBreaking > $end;
            if($break)
            {
                $rowBreakOccurs = true;
            }

            $mock = $this->createRowMock(array(0, $start), array(200, $end), $break, $rowBreakOccurs);
            $this->table->add($mock);
            $start = $end;
        }

        $result = $this->table->breakAt(220);

        $this->assertNotNull($result);
        $this->assertEquals($numberOfRows, count($result->getChildren()) + count($this->table->getChildren()));
        $this->assertEquals($tableHeight, $result->getHeight() + $this->table->getHeight());
    }

    private function createRowMock($start, $end, $break = false, $translate = false)
    {
        $methods = array('getHeight', 'getBoundary');
        if($break)
        {
            $methods[] = 'breakAt';
        }
        if($translate)
        {
            $methods[] = 'translate';
        }

        $mock = $this->getMock('PHPPdf\Core\Node\Table\Row', $methods);

        if($break)
        {
            $mock->expects($this->once())
                 ->method('breakAt')
                 ->will($this->returnValue(null));
        }

        $boundary = new Boundary();
        $boundary->setNext($start[0], $start[1])
                 ->setNext($end[0], $start[1])
                 ->setNext($end[0], $end[1])
                 ->setNext($start[0], $end[1])
                 ->close();

        $height = $start[1] - $end[1];

        $mock->expects($this->atLeastOnce())
             ->method('getBoundary')
             ->will($this->returnValue($boundary));
        $mock->expects($this->any())
             ->method('getHeight')
             ->will($this->returnValue($height));

        if($translate)
        {
            $mock->expects($this->atLeastOnce())
                 ->method('translate');
        }

        return $mock;
    }

    /**
     * @test
     * @dataProvider cellsInRowsWidthsAndColspansProvider
     */
    public function setColumnsWidthsWhenTableHasBeenNotifiedByCell(array $cellsWidthsByColumn, array $colspans, array $expectedColumnWidths)
    {
        $cells = array();

        $rows = array();
        
        foreach($cellsWidthsByColumn as $columnNumber => $cellsWidths)
        {
            foreach($cellsWidths as $rowNumber => $width)
            {
                $cell = $this->getMock('PHPPdf\Core\Node\Table\Cell', array('getWidth', 'getNumberOfColumn', 'getColspan'));
                $cell->expects($this->atLeastOnce())
                     ->method('getWidth')
                     ->will($this->returnValue($width));
                $cell->expects($this->atLeastOnce())
                     ->method('getNumberOfColumn')
                     ->will($this->returnValue($columnNumber));

                $colspan = $colspans[$columnNumber][$rowNumber];

                $cell->expects($this->atLeastOnce())
                     ->method('getColspan')
                     ->will($this->returnValue($colspan));

                $rows[$rowNumber][] = $cell;
                $cells[] = $cell;
            }
        }

        foreach($rows as $cells)
        {
            foreach($cells as $cell)
            {
                $this->table->attributeChanged($cell, 'width', null);
            }
        }

        $this->assertEquals($expectedColumnWidths, $this->table->getWidthsOfColumns());
    }

    public function cellsInRowsWidthsAndColspansProvider()
    {
        return array(
            array(
                array(
                    array(100, 200, 110), 
                    array(30, 50, 30),
                ),
                array(
                    array(1, 1, 1),
                    array(1, 1, 1),
                ),
                array(200, 50),
            ),
            array(
                array(
                    array(100, 70, 200),
                    array(30, 50),
                ),
                array(
                    array(1, 1, 2),
                    array(1, 1),
                ),
                array(125, 75),
            ),
        );
    }

    /**
     * @test
     * @dataProvider cellsInRowsMarginsProvider
     */
    public function setColumnsMarginsWhenTableHasBeenNotifiedByCell(array $cellsMarginsLeft, array $cellsMarginsRight)
    {
        $numberOfColumns = count($cellsMarginsLeft);
        $expectedMarginsLeft = $expectedMarginsRight = array_fill(0, $numberOfColumns, 0);
        
        foreach($cellsMarginsLeft as $columnNumber => $marginsLeft)
        {
            foreach($marginsLeft as $rowNumber => $marginLeft)
            {
                $marginRight = $cellsMarginsRight[$columnNumber][$rowNumber];
                
                $cell = new Cell(array(
                	'margin-left' => $marginLeft, 
                	'margin-right' => $marginRight,
                ));
                $cell->setNumberOfColumn($columnNumber);

                $expectedMarginsLeft[$columnNumber] = max($expectedMarginsLeft[$columnNumber], $marginLeft);
                $expectedMarginsRight[$columnNumber] = max($expectedMarginsRight[$columnNumber], $marginRight);
                
                $cells[] = $cell;
            }
        }

        foreach($cells as $cell)
        {
            $this->table->attributeChanged($cell, 'margin-left', 0);
            $this->table->attributeChanged($cell, 'margin-right', 0);
        }

        $this->assertEquals($expectedMarginsLeft, $this->table->getMarginsLeftOfColumns());
        $this->assertEquals($expectedMarginsRight, $this->table->getMarginsRightOfColumns());
    }

    public function cellsInRowsMarginsProvider()
    {
        return array(
            array(
                array(
                    array(10, 5, 11),
                    array(0, 23, 6),
                ), array(
                    array(5, 10, 9),
                    array(8, 11, 19),
                )
            ),
        );
    }

    /**
     * @test
     * @dataProvider setColumnsWidthsWhenRowHasBeenAddedProvider
     */
    public function setColumnsWidthsWhenRowHasBeenAdded($cellWidth, $colspan, $expectedColumnsWidth)
    {
        $row = $this->getMock('PHPPdf\Core\Node\Table\Row', array('getChildren'));
        $cell = $this->getMock('PHPPdf\Core\Node\Table\Cell', array('getWidth', 'getNumberOfColumn', 'getColspan'));

        $row->expects($this->atLeastOnce())
            ->method('getChildren')
            ->will($this->returnValue(array($cell)));
        $cell->expects($this->atLeastOnce())
             ->method('getWidth')
             ->will($this->returnValue($cellWidth));
        $cell->expects($this->atLeastOnce())
             ->method('getNumberOfColumn')
             ->will($this->returnValue(0));
        $cell->expects($this->atLeastOnce())
             ->method('getColspan')
             ->will($this->returnValue($colspan));

        $this->table->add($row);

        $this->assertEquals($expectedColumnsWidth, $this->table->getWidthsOfColumns());
    }
    
    public function setColumnsWidthsWhenRowHasBeenAddedProvider()
    {
        return array(
            array(120, 1, array(120)),
            array('70%', 1, array('70%')),
            array('70%', 2, array('35%', '35%')),
        );
    }
    
    /**
     * @test
     * @dataProvider convertColumnWidthsFromRelativeToAbsoluteProvider
     */    
    public function convertColumnWidthsFromRelativeToAbsolute($tableWidth, $actualWidthOfColumns, $expectedWidthOfColumns)
    {
        $this->table->setWidth($tableWidth);
        $this->invokeMethod($this->table, 'setWidthsOfColumns', array($actualWidthOfColumns));
        
        $this->table->convertRelativeWidthsOfColumns();
        
        $this->assertEquals($expectedWidthOfColumns, $this->table->getWidthsOfColumns());
    }
    
    public function convertColumnWidthsFromRelativeToAbsoluteProvider()
    {
        return array(
            array(100, array(100), array(100)),
            array(200, array('25%', '25%'), array(50, 50)),
            array(100, array('25%', null), array(25, 75)),
        );
    }

    /**
     * @test
     * @dataProvider cellsInRowsWidthsProvider
     */
    public function minWidthOfColumnIsMaxOfMinWidthOfColumnsCells(array $cellsInRowsMinWidths, array $colspans, $numberOfColumns)
    {
        $expectedMinWidthsOfColumns = array_fill(0, $numberOfColumns, 0);
        foreach($cellsInRowsMinWidths as $rowNumber => $cellsMinWidths)
        {
            $row = $this->getMock('PHPPdf\Core\Node\Table\Row', array('getChildren'));

            $cells = array();
            foreach ($cellsMinWidths as $columnNumber => $minWidth)
            {
                $colspan = $colspans[$rowNumber][$columnNumber];

                $cell = $this->getMock('PHPPdf\Core\Node\Table\Cell', array('getMinWidth', 'getNumberOfColumn', 'getColspan'));
                $cell->expects($this->atLeastOnce())
                     ->method('getMinWidth')
                     ->will($this->returnValue($minWidth));
                $cell->expects($this->atLeastOnce())
                     ->method('getNumberOfColumn')
                     ->will($this->returnValue($columnNumber));
                $cell->expects($this->atLeastOnce())
                     ->method('getColspan')
                     ->will($this->returnValue($colspan));
                $cells[] = $cell;

                $minWidthPerColumn = $minWidth / $colspan;
                for($i=0; $i<$colspan; $i++)
                {
                    $realColumnNumber = $columnNumber + $i;
                    $expectedMinWidthsOfColumns[$realColumnNumber] = max($expectedMinWidthsOfColumns[$realColumnNumber], $minWidthPerColumn);
                }
            }
            $row->expects($this->atLeastOnce())
                ->method('getChildren')
                ->will($this->returnValue($cells));
            $this->table->add($row);
        }

        $this->assertEquals($expectedMinWidthsOfColumns, $this->table->getMinWidthsOfColumns());
    }

    public function cellsInRowsWidthsProvider()
    {
        return array(
            array(
                array(
                    array(100, 200, 110),
                    array(30, 50, 30),
                ),
                array(
                    array(1, 1, 1,),
                    array(1, 1, 1,),
                ),
                3
            ),
            array(
                array(
                    array(100, 200),
                    array(30, 50, 30),
                ),
                array(
                    array(1, 2),
                    array(1, 1, 1,),
                ),
                3
            ),
        );
    }

    /**
     * @test
     */
    public function reduceColumnsWidthsByMargins()
    {
        $columnsWidths = array(100, 200, 150);
        $marginsLeft = array(0, 5, 10);
        $marginsRight = array(5, 5, 0);

        $this->invokeMethod($this->table, 'setWidthsOfColumns', array($columnsWidths));
        $this->invokeMethod($this->table, 'setMarginsLeftOfColumns', array($marginsLeft));
        $this->invokeMethod($this->table, 'setMarginsRightOfColumns', array($marginsRight));

        $this->table->reduceColumnsWidthsByMargins();

        array_walk($columnsWidths, function(&$value, $key) use($marginsLeft, $marginsRight)
        {
            $value -= $marginsLeft[$key] + $marginsRight[$key];
        });

        $this->assertEquals($columnsWidths, $this->table->getWidthsOfColumns());
    }
}