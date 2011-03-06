<?php

use PHPPdf\Parser\FacadeConfiguration;

class FacadeConfigurationTest extends PHPUnit_Framework_TestCase
{
    private $facadeConfiguration;

    public function setUp()
    {
        $this->facadeConfiguration = FacadeConfiguration::newInstance();
    }

    /**
     * @test
     */
    public function settingConfigFiles()
    {
        $this->facadeConfiguration->setGlyphsConfigFile('a');
        $this->assertEquals('a', $this->facadeConfiguration->getGlyphsConfigFile());

        $this->facadeConfiguration->setEnhancementsConfigFile('b');
        $this->assertEquals('b', $this->facadeConfiguration->getEnhancementsConfigFile());

        $this->facadeConfiguration->setFontsConfigFile('c');
        $this->assertEquals('c', $this->facadeConfiguration->getFontsConfigFile());

        $this->facadeConfiguration->setFormattersConfigFile('d');
        $this->assertEquals('d', $this->facadeConfiguration->getFormattersConfigFile());
    }

    /**
     * @test
     */
    public function fluentInterface()
    {
        $this->assertTrue($this->facadeConfiguration === $this->facadeConfiguration->setGlyphsConfigFile('a'));
        $this->assertTrue($this->facadeConfiguration === $this->facadeConfiguration->setEnhancementsConfigFile('a'));
        $this->assertTrue($this->facadeConfiguration === $this->facadeConfiguration->setFontsConfigFile('a'));
        $this->assertTrue($this->facadeConfiguration === $this->facadeConfiguration->setFormattersConfigFile('a'));
    }

    /**
     * @test
     */
    public function defaultConfiguration()
    {
        $this->assertNotNull($this->facadeConfiguration->getGlyphsConfigFile());
        $this->assertNotNull($this->facadeConfiguration->getEnhancementsConfigFile());
        $this->assertNotNull($this->facadeConfiguration->getFontsConfigFile());
        $this->assertNotNull($this->facadeConfiguration->getFormattersConfigFile());
    }
}