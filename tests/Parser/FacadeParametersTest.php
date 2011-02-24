<?php

use PHPPdf\Parser\FacadeParameters;

class FacadeParametersTest extends PHPUnit_Framework_TestCase
{
    private $facadeParameters;

    public function setUp()
    {
        $this->facadeParameters = FacadeParameters::newInstance();
    }

    /**
     * @test
     */
    public function settingConfigFiles()
    {
        $this->facadeParameters->setGlyphsConfigFile('a');
        $this->assertEquals('a', $this->facadeParameters->getGlyphsConfigFile());

        $this->facadeParameters->setEnhancementsConfigFile('b');
        $this->assertEquals('b', $this->facadeParameters->getEnhancementsConfigFile());

        $this->facadeParameters->setFontsConfigFile('c');
        $this->assertEquals('c', $this->facadeParameters->getFontsConfigFile());

        $this->facadeParameters->setFormattersConfigFile('d');
        $this->assertEquals('d', $this->facadeParameters->getFormattersConfigFile());
    }

    /**
     * @test
     */
    public function fluentInterface()
    {
        $this->assertTrue($this->facadeParameters === $this->facadeParameters->setGlyphsConfigFile('a'));
        $this->assertTrue($this->facadeParameters === $this->facadeParameters->setEnhancementsConfigFile('a'));
        $this->assertTrue($this->facadeParameters === $this->facadeParameters->setFontsConfigFile('a'));
        $this->assertTrue($this->facadeParameters === $this->facadeParameters->setFormattersConfigFile('a'));
    }

    /**
     * @test
     */
    public function defaultParameters()
    {
        $this->assertNotNull($this->facadeParameters->getGlyphsConfigFile());
        $this->assertNotNull($this->facadeParameters->getEnhancementsConfigFile());
        $this->assertNotNull($this->facadeParameters->getFontsConfigFile());
        $this->assertNotNull($this->facadeParameters->getFormattersConfigFile());
    }
}