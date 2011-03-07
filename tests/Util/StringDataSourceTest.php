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
    public function sourceIdIsConstantPerContent()
    {
        $content = 'some content';
        $stream = new StringDataSource($content);

        $this->assertEquals($stream->getId(), $stream->getId());

        $anotherStream = new StringDataSource('another content');

        $this->assertNotEquals($stream->getId(), $anotherStream->getId());
    }
}