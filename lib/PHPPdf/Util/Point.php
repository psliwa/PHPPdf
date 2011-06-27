<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Util;

/**
 * Point object
 *
 * Encapsulate coordinates of 2d point. This class is immutable.
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
final class Point implements \ArrayAccess
{
    private static $pool = array();

    private $x;
    private $y;

    private function __construct($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    /**
     * @return Point
     */
    public static function getInstance($x, $y)
    {
        if(!isset(self::$pool[$x][$y]))
        {
            $point = new self($x, $y);
            self::$pool[$x][$y] = $point;
        }
        
        return self::$pool[$x][$y];
    }

    public function getX()
    {
        return $this->x;
    }

    public function getY()
    {
        return $this->y;
    }

    /**
     * Transform point to array
     *
     * @return array First element is $x coord, second $y
     */
    public function toArray()
    {
        return array($this->x, $this->y);
    }

    /**
     * @param integer $x First coordinate of vector
     * @param integer $y Second coordinate of vector
     * @return PHPPdf\Util\Point Translated point by given vector
     */
    public function translate($x, $y)
    {
        return self::getInstance($this->getX() + $x, $this->getY() - $y);
    }

    public function offsetExists($offset)
    {
        return ($offset == 1 || $offset == 0);
    }

    public function offsetGet($offset)
    {
        switch($offset)
        {
            case 0:
                return $this->getX();
            case 1:
                return $this->getY();
            default:
                throw new \OutOfBoundsException(sprintf('Point implementation of ArrayAccess interface accept only "0" and "1" key, "%s" given.', $offset));
        }
    }

    public function offsetSet($offset, $value)
    {
        throw new \BadMethodCallException(sprintf('%s class is inmutable.', __CLASS__));
    }

    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException(sprintf('%s class is inmutable.', __CLASS__));
    }
}