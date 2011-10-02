<?php

namespace PHPPdf\Test\Stream;

use PHPPdf\Stream\String;
use PHPPdf\PHPUnit\Framework\TestCase;

class StringTest extends StreamTest
{
    public function setUp()
    {
        $this->stream = new String(self::EXPECTED_STREAM_CONTENT);
    }
}