<?php


namespace PHPPdf\Test\Core\Engine\Imagine\GraphicsContextTest;

use PHPPdf\Core\Engine\Imagine\GraphicsContext;

class RotationTest extends AbstractGraphicsContextTest
{
    /**
     * @test
     */
    public function givenImage_rotateBy180Deg_drawRectangleInRightBottomCorner_rectShouldBeInLeftUpperCorner()
    {
        //given

        $width = 100; $height = 50;
        $color = '#000000';

        //when

        $this
            ->gc()
            ->rotate(180)
            ->drawRectangleInRightBottomCorner($width, $height, $color)
            ->ok();

        //then

        $this->assertDrewRectInLeftUpperCorner($width, $height, $color);
    }

    /**
     * @test
     */
    public function givenImage_rotateDiagonally_rotationShouldSucceed()
    {
        //given

        $width = 100; $height = 50;
        $color = '#000000';

        //when

        $this
            ->gc()
            ->rotate(120)
            ->drawRectangleInCenter($width, $height, $color)
            ->ok();

        //then

        $this->assertImage($this->gcImage)
            ->colorAt(self::GC_WIDTH/2, self::GC_HEIGHT/2, $color);
    }

    private function gc()
    {
        return new RotationTest_GcWrapper($this->gc, self::GC_WIDTH, self::GC_HEIGHT);
    }
}

class RotationTest_GcWrapper
{
    private $gc;
    private $width;
    private $height;

    function __construct(GraphicsContext $gc, $width, $height)
    {
        $this->gc = $gc;
        $this->width = $width;
        $this->height = $height;
    }

    public function rotate($deg)
    {
        $this->gc->rotate($this->width/2, $this->height/2, deg2rad($deg));

        return $this;
    }

    public function drawRectangleInRightBottomCorner($width, $height, $color)
    {
        $this->gc->setFillColor($color);
        $this->gc->drawPolygon(
            array($this->width-$width, $this->width, $this->width, $this->width - $width),
            array(0, 0, $height, $height),
            GraphicsContext::SHAPE_DRAW_FILL
        );

        return $this;
    }

    public function drawRectangleInCenter($width, $height, $color)
    {
        $this->gc->setFillColor($color);
        $this->gc->drawPolygon(
            array($this->width/2-$width/2, $this->width/2 + $width/2, $this->width/2 + $width/2, $this->width/2-$width/2),
            array($this->height/2-$height/2, $this->height/2-$height/2, $this->height/2+$height/2, $this->height/2+$height/2),
            GraphicsContext::SHAPE_DRAW_FILL
        );

        return $this;
    }

    public function ok()
    {
        $this->gc->restoreGS();
        $this->gc->commit();
    }
}