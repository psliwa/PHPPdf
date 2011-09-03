<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Formatter;

use PHPPdf\Formatter\Formatter,
    PHPPdf\Node\Node,
    PHPPdf\Document,
    PHPPdf\Formatter\Chain;

/**
 * Base formatter class
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
abstract class BaseFormatter implements Formatter, \Serializable
{
    public function serialize()
    {
        return '';
    }

    public function unserialize($serialized)
    {
    }
}