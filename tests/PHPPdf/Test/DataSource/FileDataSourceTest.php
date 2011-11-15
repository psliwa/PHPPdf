<?php

namespace PHPPdf\Test\DataSource;

use PHPPdf\DataSource\FileDataSource;

class FileDataSourceTest extends \PHPPdf\PHPUnit\Framework\TestCase
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
        $filePath = TEST_RESOURCES_DIR.'/sample.xml';
        $stream = new FileDataSource($filePath);

        $this->assertEquals(file_get_contents($filePath), $stream->read());
    }

    /**
     * @test
     */
    public function sourceIdIsConstantPerFilePath()
    {
        $stream = new FileDataSource(TEST_RESOURCES_DIR.'/sample.xml');

        $this->assertEquals($stream->getId(), $stream->getId());

        $anotherStream = new FileDataSource(TEST_RESOURCES_DIR.'/domek.png');
        $this->assertNotEquals($stream->getId(), $anotherStream->getId());
    }
}