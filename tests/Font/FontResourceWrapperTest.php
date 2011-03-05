<?php

use PHPPdf\Font\ResourceWrapper;

class FontResourceWrapperTest extends TestCase
{
    /**
     * @test
     * @dataProvider resourceWrapperProvider
     */
    public function resourceCreatedOnlyOnce(ResourceWrapper $wrapper)
    {
        $resource = $wrapper->getResource();

        $this->assertInstanceOf('\Zend_Pdf_Resource_Font', $resource);
        $this->assertTrue($resource === $wrapper->getResource());
    }

    public function resourceWrapperProvider()
    {
        $fontPath = __DIR__.'/../resources';
        $wrapper = ResourceWrapper::fromFile($fontPath.'/verdana.ttf');

        return array(
            array($wrapper),
            array(ResourceWrapper::fromName('courier')),
        );
    }
}