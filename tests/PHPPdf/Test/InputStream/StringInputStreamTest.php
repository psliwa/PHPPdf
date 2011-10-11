<?php

namespace PHPPdf\Test\InputStream;

use PHPPdf\InputStream\StringInputStream;
use PHPPdf\PHPUnit\Framework\TestCase;

class StringInputStreamTest extends InputStreamTest
{
    public function setUp()
    {
        $this->stream = new StringInputStream(self::EXPECTED_STREAM_CONTENT);
    }
}