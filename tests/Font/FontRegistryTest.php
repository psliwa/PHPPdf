<?php

use PHPPdf\Font\Registry,
    PHPPdf\Font\ResourceWrapper,
    PHPPdf\Font\Font;

class FontRegistryTest extends PHPUnit_Framework_TestCase
{
    private $registry;

    public function setUp()
    {
        $this->registry = new Registry();
    }

    /**
     * @test
     */
    public function addingDefinition()
    {
        $fontPath = dirname(__FILE__).'/../resources';

        $this->registry->register('verdana', array(
            Font::STYLE_NORMAL => ResourceWrapper::fromFile($fontPath.'/verdana.ttf'),
            Font::STYLE_BOLD => ResourceWrapper::fromFile($fontPath.'/verdanab.ttf'),
            Font::STYLE_ITALIC => ResourceWrapper::fromFile($fontPath.'/verdanai.ttf'),
            Font::STYLE_BOLD_ITALIC => ResourceWrapper::fromFile($fontPath.'/verdanaz.ttf'),
        ));

        $font = $this->registry->get('verdana');

        $this->assertTrue($font instanceof Font);
    }

    /**
     * @test
     * @expectedException PHPPdf\Exception\Exception
     */
    public function throwExceptionIfFontDosntExist()
    {
        $this->registry->get('font');
    }

    /**
     * @test
     */
    public function unserializedRegistryIsCopyOfSerializedRegistry()
    {
        $fontPath = dirname(__FILE__).'/../resources';

        $this->registry->register('some-name', array(
            Font::STYLE_NORMAL => ResourceWrapper::fromFile($fontPath.'/verdana.ttf'),
        ));

        $unserializedRegistry = unserialize(serialize($this->registry));

        $this->assertEquals($this->registry->has('some-name'), $unserializedRegistry->has('some-name'));
    }
}