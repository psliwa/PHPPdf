<?php

use PHPPdf\Enhancement\Factory as EnhancementFactory,
    PHPPdf\Parser\EnhancementFactoryParser;

class EnhancementFactoryParserTest extends PHPUnit_Framework_TestCase
{
    private $parser;

    public function setUp()
    {
        $this->parser = new EnhancementFactoryParser();
    }

    /**
     * @test
     */
    public function parseValidEmptyXml()
    {
        $xml = '<enhancements></enhancements>';

        $enhancementFactory = $this->parser->parse($xml);

        $this->assertTrue($enhancementFactory instanceof EnhancementFactory);
    }

    /**
     * @test
     * @expectedException PHPPdf\Parser\Exception\ParseException
     */
    public function throwExceptionIfDocumentHasInvalidRoot()
    {
        $xml = '<invalid-root></invalid-root>';
        $this->parser->parse($xml);
    }

    /**
     * @test
     */
    public function parseSimpleXml()
    {
        $xml = <<<XML
<enhancements>
    <enhancement name="border" class="PHPPdf\Enhancement\Border" />
    <enhancement name="background" class="PHPPdf\Enhancement\Background" />
</enhancements>
XML;
        $enhancementFactory = $this->parser->parse($xml);

        $this->assertTrue($enhancementFactory->hasDefinition('border'));
        $this->assertTrue($enhancementFactory->hasDefinition('background'));

        $this->assertFalse($enhancementFactory->hasDefinition('somethingElse'));
    }

    /**
     * @test
     * @expectedException PHPPdf\Parser\Exception\ParseException
     */
    public function throwExceptionIfRequiredAttributesAreMissing()
    {
        $xml = <<<XML
<enhancements>
    <enhancement name="border" />
    <enhancement name="background" class="PHPPdf\Enhancement\Background" />
</enhancements>
XML;

        $this->parser->parse($xml);
    }
}