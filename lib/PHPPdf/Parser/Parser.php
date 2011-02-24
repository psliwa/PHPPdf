<?php

namespace PHPPdf\Parser;

/**
 * Generic parser interface
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface Parser
{
    public function parse($content);
}