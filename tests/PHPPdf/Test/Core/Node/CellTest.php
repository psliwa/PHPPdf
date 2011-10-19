<?php

namespace PHPPdf\Test\Core\Node;

use PHPPdf\Core\Node\Table\Cell;
use PHPPdf\Core\Node\Node;
use PHPPdf\Core\Node\Table;

class CellTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $cell;

    public function setUp()
    {
        $this->cell = new Cell();
    }

    /**
     * @test
     */
    public function unmodifableFloat()
    {
        $this->assertEquals(Node::FLOAT_LEFT, $this->cell->getFloat());
        $this->cell->setFloat(Node::FLOAT_RIGHT);
        $this->assertEquals(Node::FLOAT_LEFT, $this->cell->getFloat());
    }

    /**
     * @test
     */
    public function defaultWidth()
    {
        $this->assertTrue($this->cell->getWidth() === 0);
    }

    /**
     * @test
     */
    public function tableGetter()
    {
        $table = $this->getMock('PHPPdf\Core\Node\Table');
        $row = $this->getMock('PHPPdf\Core\Node\Table\Row');

        //internally in Node class is used $parent propery (not getParent() method) due to performance
        $this->writeAttribute($row, 'parent', $table);

        $this->cell->setParent($row);

        $this->assertTrue($table === $this->cell->getTable());
    }

    /**
     * @test
     */
    public function notifyListenersWhenAttributeHasChanged()
    {
        $listener = $this->getMock('PHPPdf\Core\Node\Listener', array('attributeChanged', 'parentBind'));
        
        $listener->expects($this->at(0))
                 ->method('attributeChanged')
                 ->with($this->cell, 'width', null);

        $listener->expects($this->at(1))
                 ->method('attributeChanged')
                 ->with($this->cell, 'width', 100);

        $listener->expects($this->at(2))
                 ->method('parentBind')
                 ->with($this->cell);

        $this->cell->addListener($listener);

        $this->cell->setAttribute('width', 100);
        $this->cell->setAttribute('width', 200);
        $this->cell->setParent(new Cell());
    }
}