<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

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