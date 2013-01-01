<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core;

use PHPPdf\Exception\BadMethodCallException;
use PHPPdf\Exception\OutOfBoundsException;

/**
 * Point object
 *
 * Encapsulate coordinates of 2d point. This class is immutable.
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
final class Point implements \ArrayAccess
{
    private static $zero;
    
    private $x;
    private $y;

    private function __construct($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    /**
     * Factory method
     * 
     * @return Point
     */
    public static function getInstance($x, $y)
    {
        if($x === 0 && $y === 0)
        {
            if(self::$zero === null)
            {
                self::$zero = new self(0, 0);
            }
            
            return self::$zero;
        }

        return new self($x, $y);
    }
    
    public function isZero()
    {
        return $this === self::$zero;
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
     * Compares y coord in given precision
     * 
     * @param Point $point Point to compare
     * @param integer $precision Precision of comparision
     * 
     * @return integer Positive number if y coord of owner is greater, 0 if values are equal or negative integer if owner is less
     */
    public function compareYCoord(Point $point, $precision = 1000)
    {
        return $this->compare($this->y, $point->y, $precision);
    }
    
    private function compare($firstNumber, $secondNumber, $precision)
    {
        if($firstNumber === $secondNumber)
        {
            return 0;
        }
        
        $firstNumberAsInteger = $this->convertToInteger($firstNumber, $precision);
        $secondNumberAsInteger = $this->convertToInteger($secondNumber, $precision);
        
        if($firstNumberAsInteger > $secondNumberAsInteger)
        {
            return 1;
        }
        elseif($firstNumberAsInteger === $secondNumberAsInteger)
        {
            return 0;
        }
        
        return -1;
    }
    
    private function convertToInteger($double, $precision)
    {
        return (int) ($double * $precision);
    }
    
    /**
     * Compares x coord in given precision
     * 
     * @param Point $point Point to compare
     * @param integer $precision Precision of comparision
     * 
     * @return integer Positive number if x coord of owner is greater, 0 if values are equal or negative integer if owner is less
     */
    public function compareXCoord(Point $point, $precision = 1000)
    {
        return $this->compare($this->x, $point->x, $precision);
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
     * @return PHPPdf\Core\Point Translated point by given vector
     */
    public function translate($x, $y)
    {
        if(!$x && !$y)
        {
            return $this;
        }

        return self::getInstance($this->x + $x, $this->y - $y);
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
                return $this->x;
            case 1:
                return $this->y;
            default:
                throw new OutOfBoundsException(sprintf('Point implementation of ArrayAccess interface accept only "0" and "1" key, "%s" given.', $offset));
        }
    }

    public function offsetSet($offset, $value)
    {
        throw new BadMethodCallException(sprintf('%s class is inmutable.', __CLASS__));
    }

    public function offsetUnset($offset)
    {
        throw new BadMethodCallException(sprintf('%s class is inmutable.', __CLASS__));
    }
}