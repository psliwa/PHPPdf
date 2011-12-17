<?php

namespace PHPPdf\Exception;

use PHPPdf\Exception as ExceptionInterface;

class DomainException extends \DomainException implements ExceptionInterface
{
}