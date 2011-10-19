<?php

namespace PHPPdf\Test\Core;

use PHPPdf\Core\FacadeConfiguration;

class FacadeConfigurationTest extends \PHPPdf\PHPUnit\Framework\TestCase
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
        $this->facadeConfiguration->setNodesConfigFile('a');
        $this->assertEquals('a', $this->facadeConfiguration->getNodesConfigFile());

        $this->facadeConfiguration->setComplexAttributesConfigFile('b');
        $this->assertEquals('b', $this->facadeConfiguration->getComplexAttributesConfigFile());

        $this->facadeConfiguration->setFontsConfigFile('c');
        $this->assertEquals('c', $this->facadeConfiguration->getFontsConfigFile());
    }

    /**
     * @test
     */
    public function fluentInterface()
    {
        $this->assertTrue($this->facadeConfiguration === $this->facadeConfiguration->setNodesConfigFile('a'));
        $this->assertTrue($this->facadeConfiguration === $this->facadeConfiguration->setComplexAttributesConfigFile('a'));
        $this->assertTrue($this->facadeConfiguration === $this->facadeConfiguration->setFontsConfigFile('a'));
    }

    /**
     * @test
     */
    public function defaultConfiguration()
    {
        $this->assertNotNull($this->facadeConfiguration->getNodesConfigFile());
        $this->assertNotNull($this->facadeConfiguration->getComplexAttributesConfigFile());
        $this->assertNotNull($this->facadeConfiguration->getFontsConfigFile());
    }
}