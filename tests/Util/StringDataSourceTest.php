<?php

use PHPPdf\Util\StringDataSource;

class StringDataSourceTest extends TestCase
{
    /**
     * @test
     */
    public function readStream()
    {
        $content = 'some content';
        $stream = new StringDataSource($content);

        $this->assertEquals($content, $stream->read());
    }

    /**
     * @test
     */
    public function crc32FromContentIsSourceId()
    {
        $content = 'some content';
        $stream = new StringDataSource($content);

        $this->assertEquals(crc32($content), $stream->getId());
    }
}