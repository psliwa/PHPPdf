<?php

namespace PHPPdf\Glyph;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface Runtime extends Glyph
{
    public function evaluate();
    public function setPage(Page $page);
}