<?php

namespace PHPPdf\Glyph\BasicList;


use PHPPdf\Glyph\GraphicsContext,
    PHPPdf\Glyph\BasicList;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface EnumerationStrategy
{
    public function drawEnumeration(BasicList $list, GraphicsContext $gc);
    public function reset();
    public function getWidthOfTheBiggestPosibleEnumerationElement(BasicList $list);
    public function setIndex($index);
    public function setVisualIndex($visualIndex);
}