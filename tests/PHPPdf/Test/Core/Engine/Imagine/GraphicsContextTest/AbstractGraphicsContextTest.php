<?php


namespace PHPPdf\Test\Core\Engine\Imagine\GraphicsContextTest;


use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Color;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Point;
use PHPPdf\Core\Engine\Imagine\GraphicsContext;
use PHPPdf\PHPUnit\Framework\TestCase;

abstract class AbstractGraphicsContextTest extends TestCase
{
    const GC_COLOR = '#ffffff';

    const GC_WIDTH = 300;
    const GC_HEIGHT = 400;

    /**
     * @var GraphicsContext
     */
    protected $gc;

    /**
     * @var ImagineInterface
     */
    protected $imagine;

    /**
     * @var ImageInterface
     */
    protected $gcImage;

    protected function setUp()
    {
        $this->imagine = new Imagine();
        $this->gcImage = $this->imagine->create(new Box(self::GC_WIDTH, self::GC_HEIGHT), new Color(self::GC_COLOR));

        $this->gc = new GraphicsContext($this->imagine, $this->gcImage);
    }


    protected function assertImage(ImageInterface $image)
    {
        return new AbstractGraphicsContextTest_ImageAssert($image);
    }

    protected function assertDrewRectInLeftUpperCorner($width, $height, $color)
    {
        $this->assertImage($this->gcImage)
            ->colorAt(1, 1, $color)
            ->colorAt($width - 2, $height - 2, $color)
            ->colorAt($width + 6, $height - 2, self::GC_COLOR)
            ->colorAt($width - 2, $height + 6, self::GC_COLOR);
    }
}

class AbstractGraphicsContextTest_ImageAssert
{
    private $image;

    public function __construct(ImageInterface $image)
    {
        $this->image = $image;
    }

    public function colorAt($x, $y, $expectedColor)
    {
        $actualColor = (string) $this->image->getColorAt(new Point($x, $y));

        $actualRgb = $this->rgb($actualColor);
        $expectedRgb = $this->rgb($expectedColor);

        for($i=0; $i<3; $i++)
        {
            \PHPUnit_Framework_Assert::assertLessThanOrEqual(
                255*0.03,//3% precision
                abs($actualRgb[$i] - $expectedRgb[$i]),
                'expected color: '.$expectedColor.', but given: '.$actualColor
            );
        }

        return $this;
    }

    private function rgb($color)
    {
        $color = str_replace('#', '', $color);

        return array(
            hexdec($color[0].$color[1]),
            hexdec($color[2].$color[3]),
            hexdec($color[4].$color[5]),
        );
    }
}