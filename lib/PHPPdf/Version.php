<?php

namespace PHPPdf;

/**
 * Current version of this library
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
final class Version
{
    const VERSION = '0.0.1-DEV';

    private function __construct()
    {
        throw new \BadMethodCallException(sprintf('Object of "%s" class can not be created.', __CLASS__));
    }
}