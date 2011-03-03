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
    public function filePathIsSourceId()
    {
        $filePath = __DIR__.'/../resources/sample.xml';
        $stream = new FileDataSource($filePath);

        $this->assertEquals($filePath, $stream->getId());
    }
}