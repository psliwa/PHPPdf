<?php

namespace PHPPdf\Test\Stream;

use PHPPdf\Stream\Stream;
use PHPPdf\Stream\String;
use PHPPdf\PHPUnit\Framework\TestCase;

class StringTest extends TestCase
{
    /**
     * @test
     */
    public function properSeeking()
    {
        $content = 'some string content';
        
        $stream = new String($content);
        
        $this->assertEquals(0, $stream->seek(10));
        $this->assertEquals(10, $stream->tell());
        $this->assertEquals(0, $stream->seek(10, Stream::SEEK_SET));
        $this->assertEquals(10, $stream->tell());
        $this->assertEquals(-1, $stream->seek(10));
        $this->assertEquals(10, $stream->tell());
        $this->assertEquals(0, $stream->seek(0, Stream::SEEK_END));
        $this->assertEquals(19, $stream->tell());
        $this->assertEquals(0, $stream->seek(-1, Stream::SEEK_END));
        $this->assertEquals(18, $stream->tell());
    }
    
    /**
     * @test
     */
    public function properReading()
    {
        $content = 'some string content';
        
        $stream = new String($content);
        
        $this->assertEquals('some', $stream->read(4));
        $this->assertEquals(' string', $stream->read(7));
        $stream->seek(-1, Stream::SEEK_END);
        $this->assertEquals('t', $stream->read(1));
        $this->assertEquals('', $stream->read(1));
        $this->assertEquals('', $stream->read(1));
        $stream->seek(-1);
        $this->assertEquals('t', $stream->read(1));
    }
}