<?php


namespace PHPPdf\Bridge\Imagine;


use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\PointInterface;
use PHPPdf\Bridge\Imagine\Image\Point;

class Rectangle
{
    private $point;
    private $box;

    public static function createWithSize(BoxInterface $size)
    {
        return new self(new Point(0, 0), $size);
    }

    public static function create(PointInterface $point, BoxInterface $size)
    {
        return new self($point, $size);
    }

    private function __construct(PointInterface $point, BoxInterface $box)
    {
        $this->point = $point;
        $this->box = $box;
    }

    public function getStartingPoint()
    {
        return $this->point;
    }

    public function getSize()
    {
        return $this->box;
    }

    public function intersection(Rectangle $rectangle)
    {
        if($this->disjoint($rectangle)) return null;

        $x1 = max($this->point->getX(), $rectangle->point->getX());
        $y1 = max($this->point->getY(), $rectangle->point->getY());

        $x2 = min($this->box->getWidth() + $this->point->getX(), $rectangle->box->getWidth() + $rectangle->point->getX());
        $y2 = min($this->box->getHeight() + $this->point->getY(), $rectangle->box->getHeight() + $rectangle->point->getY());

        return new Rectangle(new Point($x1, $y1), new Box($x2 - $x1, $y2 - $y1));
    }

    private function disjoint(Rectangle $rectangle)
    {
        return
            $rectangle->point->getX() > $this->box->getWidth() + $this->point->getX() ||
            $rectangle->point->getY() > $this->box->getHeight() + $this->point->getY() ||
            $rectangle->point->getX() + $rectangle->box->getWidth() < $this->point->getX() ||
            $rectangle->point->getY() + $rectangle->box->getHeight() < $this->point->getY();
    }
}