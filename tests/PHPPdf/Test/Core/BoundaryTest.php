<?php

namespace PHPPdf\Test\Core;

use PHPPdf\Core\Boundary;
use PHPPdf\Core\Point;

class BoundaryTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $boundary;

    public function setUp()
    {
        $this->boundary = new Boundary();
    }

    /**
     * @test
     */
    public function creation()
    {
        $this->boundary->setNext(Point::getInstance(10, 10))
                       ->setNext(20, 10)
                       ->setNext(20, 5)
                       ->setNext(Point::getInstance(10, 5));

        $this->boundary->close();

        $this->assertEquals(5, count($this->boundary));
    }

    /**
     * @test
     * @expectedException PHPPdf\Exception\LogicException
     */
    public function invalidCreation()
    {
        $this->boundary->setNext(10, 10);
        $this->boundary->close();
    }

    /**
     * @test
     * @expectedException PHPPdf\Exception\LogicException
     */
    public function invalidStateException()
    {
        $this->boundary->setNext(10, 10)
                       ->setNext(20, 10)
                       ->setNext(20, 5)
                       ->setNext(10, 5);

        $this->boundary->close();
        $this->boundary->setNext(30, 30);
    }

    /**
     * @test
     */
    public function translation()
    {
        $this->boundary->setNext(10, 10)
               ->setNext(20, 10)
               ->setNext(20, 5)
               ->setNext(10, 5);

        $old = clone $this->boundary;

        $vector = array(10, 5);

        $this->boundary->translate($vector[0], $vector[1]);

        for($it1 = $this->boundary, $it2 = $old; $it1->valid() && $it2->valid(); $it1->next(), $it2->next())
        {
            $point1 = $it1->current();
            $point2 = $it2->current();

            $this->assertEquals($point1->toArray(), array($point2->getX() + $vector[0], $point2->getY() - $vector[1]));
        }
    }

    /**
     * @test
     */
    public function translateOnePoint()
    {
        $this->boundary->setNext(10, 10)
               ->setNext(20, 10)
               ->setNext(20, 5)
               ->setNext(10, 5);

        $old = clone $this->boundary;

        $this->boundary->pointTranslate(0, 1, 1);
        foreach(array(1, 2, 3) as $index)
        {
            $this->assertEquals($old[$index], $this->boundary[$index]);
        }
        $this->assertEquals($old[0]->translate(1, 1), $this->boundary[0]);
    }
    
    /**
     * @test
     * @dataProvider pointsProvider
     */
    public function diagonalPointIsPointWithMinYCoordAndMaxXCoord(array $points)
    {
        $maxX = -(PHP_INT_MAX - 1);
        $minY = PHP_INT_MAX;
        
        foreach($points as $point)
        {
            $this->boundary->setNext($point[0], $point[1]);
            
            $maxX = max($maxX, $point[0]);
            $minY = min($minY, $point[1]);
        }
        
        $diagonalPoint = $this->boundary->getDiagonalPoint();
        
        $this->assertEquals($maxX, $diagonalPoint->getX());
        $this->assertEquals($minY, $diagonalPoint->getY());

        $this->assertEquals($points[0], $this->boundary->getFirstPoint()->toArray());
    }
    
    public function pointsProvider()
    {
        return array(
            array(
                array(
                    array(10, 10),
                    array(20, 10),
                    array(20, 5),
                    array(10, 5),
                ),
            ),
            array(
                array(
                    array(10, 10),
                    array(20, 10),
                    array(20, 5),
                    array(25, 5),
                    array(14, 7),
                ),
            ),
        );
    }

    /**
     * @test
     */
    public function arrayAccessInterface()
    {
        $this->boundary->setNext(10, 10)
               ->setNext(20, 10)
               ->setNext(20, 5)
               ->setNext(10, 5);

        $this->assertEquals(array(10, 10), $this->boundary[0]->toArray());
        $this->assertEquals(array(10, 5), $this->boundary[3]->toArray());
    }

    /**
     * @test
     * @expectedException PHPPdf\Exception\OutOfBoundsException
     */
    public function arrayAccessInvalidIndex()
    {
        $this->boundary->setNext(10, 10)
               ->setNext(20, 10);

        $this->boundary[2];
    }

    /**
     * @test
     * @expectedException PHPPdf\Exception\BadMethodCallException
     */
    public function arrayAccessInvalidOperation()
    {
        $this->boundary->setNext(10, 10)
               ->setNext(20, 10);

        $this->boundary[2] = 123;
    }
    
    /**
     * @test
     */
    public function intersecting()
    {
        $this->boundary->setNext(0, 100)
                       ->setNext(100, 100)
                       ->setNext(100, 50)
                       ->setNext(0, 50)
                       ->close();

        $this->assertTrue($this->boundary->intersects($this->boundary));

        $clone = clone $this->boundary;
        
        $clone->pointTranslate(2, 0, 100);
        $clone->pointTranslate(3, 0, 100);
        
        $clone->translate(99, 0);
        $this->assertTrue($this->boundary->intersects($clone));
        $this->assertTrue($clone->intersects($this->boundary));
        
        $clone->translate(-19, 0);
        $this->assertTrue($this->boundary->intersects($clone));
        $this->assertTrue($clone->intersects($this->boundary));
        
        $clone->translate(21, 0);
        $this->assertFalse($this->boundary->intersects($clone));
        $this->assertFalse($clone->intersects($this->boundary));

        $clone->translate(-50, -20);
        
        $this->assertTrue($this->boundary->intersects($clone));
        $this->assertTrue($clone->intersects($this->boundary));
    }
    
    /**
     * @test
     */
    public function intersectingOccursWhenAtLeastOnePointIsContainedInBySecondBoundary()
    {
        $this->boundary->setNext(0, 100)
                       ->setNext(100, 100)
                       ->setNext(100, 0)
                       ->setNext(0, 0)
                       ->close();
        $secondBoundary = new Boundary();
        $secondBoundary->setNext(99, 100)
                       ->setNext(190, 100)
                       ->setNext(190, 50)
                       ->setNext(99, 50)
                       ->close();
                       
        $this->assertTrue($this->boundary->intersects($secondBoundary));
        $this->assertTrue($secondBoundary->intersects($this->boundary));
    }
    
    /**
     * @test
     */
    public function getMiddlePoint()
    {
        $this->boundary->setNext(0, 100)
                       ->setNext(100, 100)
                       ->setNext(100, 0)
                       ->setNext(0, 0)
                       ->close();
                       
        $this->assertEquals(array(50, 50), $this->boundary->getMiddlePoint()->toArray());
        
        $this->boundary->translate(50, 50);
        
        $this->assertEquals(array(100, 0), $this->boundary->getMiddlePoint()->toArray());        
    }
}