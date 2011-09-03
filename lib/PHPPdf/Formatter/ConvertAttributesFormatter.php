<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Formatter;

use PHPPdf\Formatter\BaseFormatter,
    PHPPdf\Node\Node,
    PHPPdf\Util,
    PHPPdf\Document;

/**
 * Convert values of some attributes
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ConvertAttributesFormatter extends BaseFormatter
{
    public function format(Node $node, Document $document)
    {
        $this->convertPercentageDimensions($node);
        $this->convertAutoMargins($node);
        $this->convertDegreesToRadians($node);
    }

    private function convertPercentageDimensions(Node $node)
    {       
        $node->convertScalarAttribute('width');
        $node->convertScalarAttribute('height');
    }

    private function convertFromPercentageValue($value, $percent)
    {
        return Util::convertFromPercentageValue($percent, $value);
    }

    private function convertAutoMargins(Node $node)
    {
        $parent = $node->getParent();

        if($parent !== null && $this->hasAutoMargins($node))
        {
            $parentWidth = $parent->getWidthWithoutPaddings();
            $nodeWidth = $node->getWidth();

            if($nodeWidth > $parentWidth)
            {
                $parentWidth = $nodeWidth;
                $parent->setWidth($nodeWidth);
            }

            $node->hadAutoMargins(true);
            $width = $node->getWidth() === null ? $parentWidth : $node->getWidth();
            
            //adds horizontal paddings, becouse dimension formatter hasn't executed yet
            $width += $node->getPaddingLeft() + $node->getPaddingRight();

            $margin = ($parentWidth - $width)/2;
            $node->setMarginLeft($margin);
            $node->setMarginRight($margin);
        }
    }

    private function hasAutoMargins(Node $node)
    {
        $marginLeft = $node->getMarginLeft();
        $marginRight = $node->getMarginRight();

        return ($marginLeft === Node::MARGIN_AUTO && $marginRight === Node::MARGIN_AUTO);
    }
    
    private function convertDegreesToRadians(Node $node)
    {
        $rotate = $node->getAttribute('rotate');
        
        if($rotate !== null && strpos($rotate, 'deg') !== false)
        {
            $degrees = (float) $rotate;
            $radians = deg2rad($degrees);
            $node->setAttribute('rotate', $radians);
        }
    }
}