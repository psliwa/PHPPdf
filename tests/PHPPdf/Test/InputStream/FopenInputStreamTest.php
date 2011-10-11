<?php

namespace PHPPdf\Test\InputStream;

use PHPPdf\InputStream\FopenInputStream;

class FopenInputStreamTest extends InputStreamTest
{
    public function setUp()
    {
        $this->stream = new FopenInputStream(TEST_RESOURCES_DIR.'/test.txt', 'rb');
    }
}