<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Bridge\Zend\Pdf;

use ZendPdf\Page as ZendPage;
use ZendPdf\InternalType;

/**
 * Modified ZendPdf\Page class
 * 
 * Roundings are circles of radius = $radius in contrast to Zend_Pdf.
 */
class Page extends ZendPage
{
    public function drawRoundedRectangle($x1, $y1, $x2, $y2, $radius,
                                         $fillType = self::SHAPE_DRAW_FILL_AND_STROKE)
    {

        $this->_addProcSet('PDF');

        if(!is_array($radius)) {
            $radius = array($radius, $radius, $radius, $radius);
        } else {
            for ($i = 0; $i < 4; $i++) {
                if(!isset($radius[$i])) {
                    $radius[$i] = 0;
                }
            }
        }

        $topLeftX      = $x1;
        $topLeftY      = $y2;
        $topRightX     = $x2;
        $topRightY     = $y2;
        $bottomRightX  = $x2;
        $bottomRightY  = $y1;
        $bottomLeftX   = $x1;
        $bottomLeftY   = $y1;

        //draw top side
        $x1Obj = new InternalType\NumericObject($topLeftX + $radius[0]);
        $y1Obj = new InternalType\NumericObject($topLeftY);
        $this->_contents .= $x1Obj->toString() . ' ' . $y1Obj->toString() . " m\n";
        $x1Obj = new InternalType\NumericObject($topRightX - $radius[1]);
        $y1Obj = new InternalType\NumericObject($topRightY);
        $this->_contents .= $x1Obj->toString() . ' ' . $y1Obj->toString() . " l\n";

        //draw top right corner if needed
        if ($radius[1] != 0) {
            $x1Obj = new InternalType\NumericObject($topRightX - $radius[1]);
            $y1Obj = new InternalType\NumericObject($topRightY);
            $x2Obj = new InternalType\NumericObject($topRightX);
            $y2Obj = new InternalType\NumericObject($topRightY);
            $x3Obj = new InternalType\NumericObject($topRightX);
            $y3Obj = new InternalType\NumericObject($topRightY - $radius[1]);
            $this->_contents .= $x1Obj->toString() . ' ' . $y1Obj->toString() . ' '
                              . $x2Obj->toString() . ' ' . $y2Obj->toString() . ' '
                              . $x3Obj->toString() . ' ' . $y3Obj->toString() . ' '
                              . " c\n";
        }

        //draw right side
        $x1Obj = new InternalType\NumericObject($bottomRightX);
        $y1Obj = new InternalType\NumericObject($bottomRightY + $radius[2]);
        $this->_contents .= $x1Obj->toString() . ' ' . $y1Obj->toString() . " l\n";

        //draw bottom right corner if needed
        if ($radius[2] != 0) {
            $x1Obj = new InternalType\NumericObject($bottomRightX);
            $y1Obj = new InternalType\NumericObject($bottomRightY);
            $x2Obj = new InternalType\NumericObject($bottomRightX - $radius[1]);
            $y2Obj = new InternalType\NumericObject($bottomRightY);
            $x3Obj = new InternalType\NumericObject($bottomRightX - $radius[2]);
            $y3Obj = new InternalType\NumericObject($bottomRightY);
            $this->_contents .= $x1Obj->toString() . ' ' . $y1Obj->toString() . ' '
                              . $x2Obj->toString() . ' ' . $y2Obj->toString() . ' '
                              . $x3Obj->toString() . ' ' . $y3Obj->toString() . ' '
                              . " c\n";
        }

        //draw bottom side
        $x1Obj = new InternalType\NumericObject($bottomLeftX + $radius[3]);
        $y1Obj = new InternalType\NumericObject($bottomLeftY);
        $this->_contents .= $x1Obj->toString() . ' ' . $y1Obj->toString() . " l\n";

        //draw bottom left corner if needed
        if ($radius[3] != 0) {
            $x1Obj = new InternalType\NumericObject($bottomLeftX + $radius[1]);
            $y1Obj = new InternalType\NumericObject($bottomLeftY);
            $x2Obj = new InternalType\NumericObject($bottomLeftX);
            $y2Obj = new InternalType\NumericObject($bottomLeftY);
            $x3Obj = new InternalType\NumericObject($bottomLeftX);
            $y3Obj = new InternalType\NumericObject($bottomLeftY + $radius[3]);
            $this->_contents .= $x1Obj->toString() . ' ' . $y1Obj->toString() . ' '
                              . $x2Obj->toString() . ' ' . $y2Obj->toString() . ' '
                              . $x3Obj->toString() . ' ' . $y3Obj->toString() . ' '
                              . " c\n";
        }

        //draw left side
        $x1Obj = new InternalType\NumericObject($topLeftX);
        $y1Obj = new InternalType\NumericObject($topLeftY - $radius[0]);
        $this->_contents .= $x1Obj->toString() . ' ' . $y1Obj->toString() . " l\n";

        //draw top left corner if needed
        if ($radius[0] != 0) {
            $x1Obj = new InternalType\NumericObject($topLeftX);
            $y1Obj = new InternalType\NumericObject($topLeftY);
            $x2Obj = new InternalType\NumericObject($topLeftX + $radius[1]);
            $y2Obj = new InternalType\NumericObject($topLeftY);
            $x3Obj = new InternalType\NumericObject($topLeftX + $radius[0]);
            $y3Obj = new InternalType\NumericObject($topLeftY);
            $this->_contents .= $x1Obj->toString() . ' ' . $y1Obj->toString() . ' '
                              . $x2Obj->toString() . ' ' . $y2Obj->toString() . ' '
                              . $x3Obj->toString() . ' ' . $y3Obj->toString() . ' '
                              . " c\n";
        }

        switch ($fillType) {
            case self::SHAPE_DRAW_FILL_AND_STROKE:
                $this->_contents .= " B*\n";
                break;
            case self::SHAPE_DRAW_FILL:
                $this->_contents .= " f*\n";
                break;
            case self::SHAPE_DRAW_STROKE:
                $this->_contents .= " S\n";
                break;
        }

        return $this;
    }
}