<?php

use PHPPdf\Parser\FontRegistryParser,
    PHPPdf\Font\Font;

class FontRegistryParserTest extends PHPUnit_Framework_TestCase
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

        $fonts = $this->parser->parse($xml);

        $this->assertEquals(0, count($fonts));
    }

    /**
     * @test
     * @dataProvider xmlProvider
     */
    public function parseSimpleXml($xml)
    {
        $fonts = $this->parser->parse($xml);

        $this->assertEquals(1, count($fonts));

        $font = $fonts['verdana'];
        $styles = array(Font::STYLE_NORMAL => true, Font::STYLE_BOLD => true, Font::STYLE_ITALIC => false, Font::STYLE_BOLD_ITALIC => true);

        foreach($styles as $style => $has)
        {
            $this->assertEquals($has, $font->hasStyle($style));
        }
    }
    
    public function xmlProvider()
    {
        $xml1 = <<<XML
<fonts>
    <font name="verdana">
        <bold file="%resources%/fonts/verdana/bold.ttf" />
        <normal file="%resources%/fonts/verdana/normal.ttf" />
        <bold-italic file="%resources%/fonts/verdana/bold+italic.ttf" />
    </font>
</fonts>
XML;
        
        $xml2 = <<<XML
<fonts>
    <font name="verdana">
        <bold type="courier-bold" />
        <normal type="courier" />
        <bold-italic type="courier-oblique" />
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
    public function throwExceptionIfFontNameIsMissing()
    {
        $xml = <<<XML
<fonts>
    <font>
        <normal file="%resources%/fonts/verdana/normal.ttf" />
    </font>
</fonts>
XML;
        $this->parser->parse($xml);
    }

    /**
     * @test
     * @expectedException \PHPPdf\Parser\Exception\ParseException
     */
    public function throwExceptionIfFontStylesAreInvalid()
    {
        $xml = <<<XML
<fonts>
    <font name="verdana">
        <normal />
    </font>
</fonts>
XML;
        $this->parser->parse($xml);
    }

    /**
     * @test
     * @expectedException \PHPPdf\Parser\Exception\ParseException
     */
    public function throwExceptionIfFontTypeDosntExist()
    {
        $xml = <<<XML
<fonts>
    <font name="verdana">
        <normal type="some-type" />
    </font>
</fonts>
XML;
        $this->parser->parse($xml);
    }
}