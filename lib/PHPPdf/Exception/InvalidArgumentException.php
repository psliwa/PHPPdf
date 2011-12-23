<?php

namespace PHPPdf\Exception;

use PHPPdf\Exception as ExceptionInterface;

class InvalidArgumentException extends \InvalidArgumentException implements ExceptionInterface
{
}