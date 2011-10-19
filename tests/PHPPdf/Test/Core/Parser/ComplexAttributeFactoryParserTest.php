<?php

namespace PHPPdf\Test\Core\Parser;

use PHPPdf\Core\ComplexAttribute\ComplexAttributeFactory,
    PHPPdf\Core\Parser\ComplexAttributeFactoryParser;

class ComplexAttributeFactoryParserTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $parser;

    public function setUp()
    {
        $this->parser = new ComplexAttributeFactoryParser();
    }

    /**
     * @test
     */
    public function parseValidEmptyXml()
    {
        $xml = '<complex-attributes></complex-attributes>';

        $complexAttributeFactory = $this->parser->parse($xml);

        $this->assertTrue($complexAttributeFactory instanceof ComplexAttributeFactory);
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
<complex-attributes>
    <complex-attribute name="border" class="PHPPdf\Core\ComplexAttribute\Border" />
    <complex-attribute name="background" class="PHPPdf\Core\ComplexAttribute\Background" />
</complex-attributes>
XML;
        $complexAttributeFactory = $this->parser->parse($xml);

        $this->assertTrue($complexAttributeFactory->hasDefinition('border'));
        $this->assertTrue($complexAttributeFactory->hasDefinition('background'));

        $this->assertFalse($complexAttributeFactory->hasDefinition('somethingElse'));
    }

    /**
     * @test
     * @expectedException PHPPdf\Parser\Exception\ParseException
     */
    public function throwExceptionIfRequiredAttributesAreMissing()
    {
        $xml = <<<XML
<complex-attributes>
    <complex-attribute name="border" />
    <complex-attribute name="background" class="PHPPdf\Core\ComplexAttribute\Background" />
</complex-attributes>
XML;

        $this->parser->parse($xml);
    }
}