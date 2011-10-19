<?php

namespace PHPPdf\Test\DataSource;

use PHPPdf\DataSource\StringDataSource;

class StringDataSourceTest extends \PHPPdf\PHPUnit\Framework\TestCase
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