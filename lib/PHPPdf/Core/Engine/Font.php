<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Engine;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface Font
{
    const STYLE_NORMAL = 0;
    const STYLE_BOLD = 1;
    const STYLE_ITALIC = 2;
    const STYLE_BOLD_ITALIC = 3;
    const STYLE_LIGHT = 4;
    const STYLE_LIGHT_ITALIC = 5;

    public function hasStyle($style);
    public function setStyle($style);
    public function getCurrentStyle();

    public function getCurrentResourceIdentifier();
    public function getWidthOfText($text, $fontSize);
}