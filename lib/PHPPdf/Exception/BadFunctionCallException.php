<?php

namespace PHPPdf\Exception;

use PHPPdf\Exception as ExceptionInterface;

class BadFunctionCallException extends \BadFunctionCallException implements ExceptionInterface
{
}