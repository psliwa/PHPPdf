<?php

use PHPPdf\Util\FileDataSource;

class FileDataSourceTest extends TestCase
{
    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function throwExceptionIfFileDosntExist()
    {
        new FileDataSource('some file');
    }

    /**
     * @test
     */
    public function readFileContent()
    {
        $filePath = __DIR__.'/../resources/sample.xml';
        $stream = new FileDataSource($filePath);

        $this->assertEquals(file_get_contents($filePath), $stream->read());
    }

    /**
     * @test
     */
    public function sourceIdIsConstantPerFilePath()
    {
        $stream = new FileDataSource( __DIR__.'/../resources/sample.xml');

        $this->assertEquals($stream->getId(), $stream->getId());

        $anotherStream = new FileDataSource(__DIR__.'/../resources/domek-min.jpg');
        $this->assertNotEquals($stream->getId(), $anotherStream->getId());
    }
}