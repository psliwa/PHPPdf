<?php

namespace PHPPdf\Test\Stream;

use PHPPdf\Stream\Stream;
use PHPPdf\PHPUnit\Framework\TestCase;

abstract class StreamTest extends TestCase
{
    const EXPECTED_STREAM_CONTENT = 'some string content';
    
    protected $stream;
    
    /**
     * @test
     */
    public function properSeeking()
    {        
        $this->assertEquals(0, $this->stream->seek(10));
        $this->assertEquals(10, $this->stream->tell());
        $this->assertEquals(0, $this->stream->seek(10, Stream::SEEK_SET));
        $this->assertEquals(10, $this->stream->tell());
        $this->assertEquals(0, $this->stream->seek(10));
        $this->assertEquals(20, $this->stream->tell());
        $this->assertEquals(0, $this->stream->seek(0, Stream::SEEK_END));
        $this->assertEquals(19, $this->stream->tell());
        $this->assertEquals(0, $this->stream->seek(-1, Stream::SEEK_END));
        $this->assertEquals(18, $this->stream->tell());
    }
    
    /**
     * @test
     */
    public function properReading()
    {        
        $this->assertEquals('some', $this->stream->read(4));
        $this->assertEquals(' string', $this->stream->read(7));
        $this->stream->seek(-1, Stream::SEEK_END);
        $this->assertEquals('t', $this->stream->read(1));
        $this->assertEquals('', $this->stream->read(1));
        $this->assertEquals('', $this->stream->read(1));
        $this->stream->seek(-1);
        $this->assertEquals('t', $this->stream->read(1));
    }
    
    /**
     * @test
     */
    public function size()
    {
        $this->assertEquals(strlen(self::EXPECTED_STREAM_CONTENT), $this->stream->size());
    }

    protected function tearDown()
    {
        $this->stream->close();
    }
}