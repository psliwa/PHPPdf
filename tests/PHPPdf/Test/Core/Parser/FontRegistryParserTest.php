<?php

namespace PHPPdf\Test\Core\Parser;

use PHPPdf\Core\Engine\Font;
use PHPPdf\Core\Parser\FontRegistryParser;

class FontRegistryParserTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $parser;

    public function setUp()
    {
        $this->parser = new FontRegistryParser();
    }

    /**
     * @test
     */
    public function parseValidEmptyXml()
    {
        $xml = '<fonts></fonts>';

        $fontDefinitions = $this->parser->parse($xml);

        $this->assertEquals(0, count($fontDefinitions));
    }

    /**
     * @test
     * @dataProvider xmlProvider
     */
    public function parseSimpleXml($xml)
    {
        $fontDefinitions = $this->parser->parse($xml);

        $this->assertEquals(1, count($fontDefinitions));

        $font = $fontDefinitions['font'];
        $styles = array(Font::STYLE_NORMAL => true, Font::STYLE_BOLD => true, Font::STYLE_ITALIC => false, Font::STYLE_BOLD_ITALIC => true);

        foreach($styles as $style => $has)
        {
            $this->assertEquals($has, isset($font[$style]));
        }
    }
    
    public function xmlProvider()
    {
        $xml1 = <<<XML
<fonts>
    <font name="font">
        <bold src="%resources%/fonts/judson/bold.ttf" />
        <normal src="%resources%/fonts/judson/normal.ttf" />
        <bold-italic src="%resources%/fonts/judson/italic.ttf" />
    </font>
</fonts>
XML;
        
        $xml2 = <<<XML
<fonts>
    <font name="font">
        <bold src="courier-bold" />
        <normal src="courier" />
        <bold-italic src="courier-oblique" />
    </font>
</fonts>
XML;
        
        return array(
            array($xml1),
            array($xml2),
        );
    }

    /**
     * @test
     * @expectedException \PHPPdf\Parser\Exception\ParseException
     */
    public function throwExceptionIfFontStylesAreInvalid()
    {
        $xml = <<<XML
<fonts>
    <font name="font">
        <normal />
    </font>
</fonts>
XML;
        $this->parser->parse($xml);
    }
}