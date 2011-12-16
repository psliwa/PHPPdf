<?php

/*
 * This file is part of the Imagine package.
 *
 * (c) Bulat Shakirzyanov <mallluhuct@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Imagine\Image;

use Imagine\Exception\InvalidArgumentException;

/**
 * Replacement for standard Imagine\Image\Box class. 
 * 
 * Box::contains method always returns true in order to ability to paste not entire
 * image into another image. This feature is used in drawing image background of Containers
 * in PHPPdf library.
 */
class Box implements BoxInterface
{
    private $width;
    private $height;
    public function __construct($width, $height)
    {
        if ($height < 1 || $width < 1) {
            throw new InvalidArgumentException(sprintf(
                'Length of either side cannot be 0 or negative, current size '.
                'is %sx%s', $width, $height
            ));
        }

        $this->width  = (int) $width;
        $this->height = (int) $height;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function scale($ratio)
    {
        return new Box(round($ratio * $this->width), round($ratio * $this->height));
    }

    public function increase($size)
    {
        return new Box((int) $size + $this->width, (int) $size + $this->height);
    }

    public function contains(BoxInterface $box, PointInterface $start = null)
    {
        //overwritten behaviour, more info in class comment
        return true;
    }

    public function square()
    {
        return $this->width * $this->height;
    }

    public function __toString()
    {
        return sprintf('%dx%d px', $this->width, $this->height);
    }

    public function widen($width)
    {
        return $this->scale($width / $this->width);
    }

    public function heighten($height)
    {
        return $this->scale($height / $this->height);
    }
}
