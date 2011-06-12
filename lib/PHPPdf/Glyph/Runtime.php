<?php

namespace PHPPdf\Glyph;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface Runtime
{
    public function evaluate();
    public function setPage(Page $page);
}