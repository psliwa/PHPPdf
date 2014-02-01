<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf;

use PHPPdf\Exception\BadMethodCallException;

/**
 * Current version of this library
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
final class Version
{
<<<<<<< HEAD
    const VERSION = '1.2.2';
=======
    const VERSION = '1.2.3-DEV';
>>>>>>> master

    private function __construct()
    {
        throw new BadMethodCallException(sprintf('Object of "%s" class can not be created.', __CLASS__));
    }
}
