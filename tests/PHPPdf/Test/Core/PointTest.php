<?php

namespace PHPPdf\Test\Core;

use PHPPdf\Core\Point;

class PointTest extends \PHPPdf\PHPUnit\Framework\TestCase
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
     * @expectedException PHPPdf\Exception\OutOfBoundsException
     */
    public function throwExceptionIfArrayAccessIsBadCall()
    {
        $point = Point::getInstance(10, 5);
        $point[2];
    }

    /**
     * @test
     * @expectedException PHPPdf\Exception\BadMethodCallException
     */
    public function throwExceptionIfArrayAccessSetMethodIsInvoked()
    {
        $point = Point::getInstance(10, 10);
        $point[1] = 5;
    }
    
    /**
     * @test
     * @dataProvider compareCoordinationsProvider
     */
    public function compareCoordinations(Point $firstPoint, Point $secondPoint, $precision, $expectedXCompare, $expectedYCompare)
    {
        $actualXCompare = $firstPoint->compareXCoord($secondPoint, $precision);
        $actualYCompare = $firstPoint->compareYCoord($secondPoint, $precision);
        
        $this->assertEquals($expectedXCompare, $actualXCompare);
        $this->assertEquals($expectedYCompare, $actualYCompare);
    }
    
    public function compareCoordinationsProvider()
    {
        return array(
            array(Point::getInstance(1.12345678, 2.98765421), Point::getInstance(1.12345611, 2.98765422), 100000, 0, 0),
            array(Point::getInstance(1.12345678, 2.98765421), Point::getInstance(1.12345611, 2.98765432), 10000000, 1, -1),
            array(Point::getInstance(1.123456781, 2.987654291), Point::getInstance(1.123456791, 2.98765428), 100000000, -1, 1),
        );
    }
}
