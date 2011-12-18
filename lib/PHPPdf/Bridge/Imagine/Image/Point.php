<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Bridge\Imagine\Image;

use Imagine\Image\BoxInterface;
use Imagine\Image\PointInterface;

/**
 * Point, coordinates might to have negative values
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Point implements PointInterface
{
    public function __construct($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function getX()
    {
        return $this->x;
    }

    public function getY()
    {
        return $this->y;
    }

    public function in(BoxInterface $box)
    {
        return $this->x < $box->getWidth() && $this->y < $box->getHeight();
    }

    public function move($amount)
    {
        return new self($this->x + $amount, $this->y + $amount);
    }

    public function __toString()
    {
        return sprintf('(%d, %d)', $this->x, $this->y);
    }
}