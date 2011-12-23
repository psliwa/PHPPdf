<?php

namespace PHPPdf\Exception;

use PHPPdf\Exception as ExceptionInterface;

class OutOfBoundsException extends \OutOfBoundsException implements ExceptionInterface
{
}