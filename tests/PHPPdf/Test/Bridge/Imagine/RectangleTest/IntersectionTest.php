<?php


namespace PHPPdf\Test\Bridge\Imagine\RectangleTest;

use Imagine\Image\Box;
use Imagine\Image\Point;
use PHPPdf\Bridge\Imagine\Rectangle;
use PHPPdf\PHPUnit\Framework\TestCase;

class IntersectionTest extends TestCase
{
    /**
     * @test
     */
    public function secondRectIsInsideFirstOne_returnSecondRect()
    {
        $rect1 = Rectangle::create(new Point(0, 0), new Box(100, 100));
        $rect2 = Rectangle::create(new Point(20, 20), new Box(30, 30));

        $this->assertIntersection($rect2, $rect1, $rect2);
    }

    private function assertIntersection(Rectangle $expected, Rectangle $rect1, Rectangle $rect2)
    {
        $this->assertEquals(self::rectToArray($expected), self::rectToArray($rect1->intersection($rect2)));
        $this->assertEquals(self::rectToArray($expected), self::rectToArray($rect2->intersection($rect1)));
    }

    private static function rectToArray(Rectangle $rect)
    {
        return array(
            $rect->getStartingPoint()->getX(),
            $rect->getStartingPoint()->getY(),
            $rect->getSize()->getWidth(),
            $rect->getSize()->getHeight()
        );
    }

    /**
     * @test
     */
    public function rectanglesAreDisjoint_returnNull()
    {
        $rect1 = Rectangle::create(new Point(0, 0), new Box(100, 100));
        $rect2 = Rectangle::create(new Point(200, 200), new Box(100, 100));

        $this->assertNull($rect1->intersection($rect2));
    }

    /**
     * @test
     */
    public function verticesOfRectsAreOutsideRects_returnIntersection()
    {
        $rect1 = Rectangle::create(new Point(50, 50), new Box(100, 100));
        $rect2 = Rectangle::create(new Point(60, 0), new Box(30, 500));

        $this->assertIntersection(
            Rectangle::create(new Point(60, 50), new Box(30, 100)),
            $rect1,
            $rect2
        );
    }

    /**
     * @test
     */
    public function verticesOfOneRectArePartiallyInSecondRect_returnIntersection()
    {
        $rect1 = Rectangle::create(new Point(50, 50), new Box(100, 100));
        $rect2 = Rectangle::create(new Point(40, 40), new Box(50, 50));

        $this->assertIntersection(
            Rectangle::create(new Point(50, 50), new Box(40, 40)),
            $rect1,
            $rect2
        );
    }
}