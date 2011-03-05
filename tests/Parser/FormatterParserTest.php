<?php

use PHPPdf\Parser\FormatterParser,
    PHPPdf\Document;

class FormatterParserTest extends PHPUnit_Framework_TestCase
{
    private $parser = null;

    public function setUp()
    {
        $this->parser = new FormatterParser();
    }

    /**
     * @test
     */
    public function parseValidEmptyXml()
    {
        $xml = '<formatters></formatters>';

        $formatters = $this->parser->parse($xml);
        $this->assertEquals(array(), $formatters);
    }

    /**
     * @test
     */
    public function parseSimpleXml()
    {
        $xml = <<<XML
<formatters>
    <formatter class="PHPPdf\Formatter\DebugFormatter" />
    <formatter class="PHPPdf\Formatter\TableFormatter" />
</formatters>
XML;
        $formatters = $this->parser->parse($xml);

        $this->assertEquals(2, count($formatters));
        $this->assertInstanceOf('PHPPdf\Formatter\DebugFormatter', $formatters[0]);
        $this->assertInstanceOf('PHPPdf\Formatter\TableFormatter', $formatters[1]);
    }

    /**
     * @test
     * @expectedException PHPPdf\Parser\Exception\ParseException
     */
    public function throwsExceptionIfClassAttributeIsMissing()
    {
        $xml = <<<XML
<formatters>
    <formatter clas="PHPPdf\Formatter\DebugFormatter" />
</formatters>
XML;

        $this->parser->parse($xml);
    }

    /**
     * @test
     * @expectedException PHPPdf\Parser\Exception\ParseException
     */
    public function throwsExceptionIfInvalidTagOccur()
    {
        $xml = <<<XML
<formatters>
    <formater class="PHPPdf\Formatter\DebugFormatter" />
</formatters>
XML;
        $this->parser->parse($xml);
    }

    /**
     * @test
     * @expectedException PHPPdf\Parser\Exception\ParseException
     */
    public function throwsExceptionIfClassDosntExist()
    {
        $xml = <<<XML
<formatters>
    <formatter class="Unexisted\Class" />
</formatters>
XML;
        $this->parser->parse($xml);
    }

    /**
     * @test
     * @expectedException PHPPdf\Parser\Exception\ParseException
     */
    public function throwsExceptionIfClassDosntExtendFormatter()
    {
        $xml = <<<XML
<formatters>
    <formatter class="stdClass" />
</formatters>
XML;
        $this->parser->parse($xml);
    }
}