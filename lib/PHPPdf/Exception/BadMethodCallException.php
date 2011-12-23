<?php

namespace PHPPdf\Exception;

use PHPPdf\Exception as ExceptionInterface;

class BadMethodCallException extends \BadMethodCallException implements ExceptionInterface
{
}