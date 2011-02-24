<?php

use PHPPdf\Util\Point;

class PointTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function factory()
    {
        $point1 = Point::getInstance(10, 10);
        $point2 = Point::getInstance(10, 10);
        $point3 = Point::getInstance(10, 11);

        $this->assertEquals($point1, $point2);
        $this->assertNotEquals($point1, $point3);
        $this->assertEquals(10, $point1->getX());
        $this->assertEquals(array(10, 11), $point3->toArray());
    }
    
    /**
     * @test
     */
    public function translation()
    {
        $point1 = Point::getInstance(10, 10);
        $point2 = $point1->translate(10, 10);

        $this->assertNotEquals($point1, $point2);
        $this->assertEquals(array(20, 0), $point2->toArray());
    }

    /**
     * @test
     */
    public function arrayAccessInterface()
    {
        $point = Point::getInstance(10, 15);

        $this->assertEquals(10, $point[0]);
        $this->assertEquals(15, $point[1]);
        $this->assertTrue(isset($point[0]));
        $this->assertFalse(isset($point[2]));
    }

    /**
     * @test
     * @expectedException \OutOfBoundsException
     */
    public function throwExceptionIfArrayAccessIsBadCall()
    {
        $point = Point::getInstance(10, 5);
        $point[2];
    }

    /**
     * @test
     * @expectedException \BadMethodCallException
     */
    public function throwExceptionIfArrayAccessSetMethodIsInvoked()
    {
        $point = Point::getInstance(10, 10);
        $point[1] = 5;
    }
}
