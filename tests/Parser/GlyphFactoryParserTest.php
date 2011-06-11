<?php

use PHPPdf\Parser\GlyphFactoryParser,
    PHPPdf\Parser\StylesheetParser,
    PHPPdf\Glyph\Factory as GlyphFactory;

class GlyphFactoryParserTest extends TestCase
{
    private $parser;

    public function setUp()
    {
        $this->parser = new GlyphFactoryParser();
    }

    /**
     * @test
     */
    public function parseValidEmptyXml()
    {
        $xml = '<glyphs></glyphs>';

        $glyphFactory = $this->parser->parse($xml);

        $this->assertTrue($glyphFactory instanceof GlyphFactory);
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
    public function gettingAndSettingStylesheetParser()
    {
        $defaultStylesheetParser = $this->parser->getStylesheetParser();

        $this->assertTrue($defaultStylesheetParser instanceof StylesheetParser);
    }

    /**
     * @test
     */
    public function parseSimpleXml()
    {
        $xml = <<<XML
<glyphs>
    <glyph name="div" class="PHPPdf\Glyph\Container">
    </glyph>
    <glyph name="p" class="PHPPdf\Glyph\Container">
    </glyph>
</glyphs>
XML;
        $glyphFactory = $this->parser->parse($xml);

        $this->assertTrue($glyphFactory->hasPrototype('div'));
        $this->assertTrue($glyphFactory->hasPrototype('p'));
        
        $this->assertFalse($glyphFactory->hasPrototype('anotherTag'));

        $this->assertInstanceOf('PHPPdf\Glyph\Container', $glyphFactory->getPrototype('div'));
    }

    /**
     * @test
     * @expectedException PHPPdf\Parser\Exception\ParseException
     */
    public function throwExceptionIfRequiredAttributesAreMissing()
    {
        $xml = <<<XML
<glyphs>
    <glyph name="div">
    </glyph>
</glyphs>
XML;
        $this->parser->parse($xml);
    }

    /**
     * @test
     */
    public function useStylesheetParserForStylesheetParsing()
    {
        $xml = <<<XML
<glyphs>
    <glyph name="div" class="PHPPdf\Glyph\Container">
        <stylesheet>
        </stylesheet>
    </glyph>
</glyphs>
XML;

        $attributes = array('display' => 'inline', 'splittable' => false);
        $enhancements = array('name' => array('name' => 'value'));
        $attributeBagMock = $this->getMock('PHPPdf\Util\AttributeBag', array('getAll'));
        $attributeBagMock->expects($this->once())
                         ->method('getAll')
                         ->will($this->returnValue($attributes));

        $enhancementBagMock = $this->getMock('PHPPdf\Enhancement\EnhancementBag', array('getAll'));
        $enhancementBagMock->expects($this->once())
                         ->method('getAll')
                         ->will($this->returnValue($enhancements));

        $bagContainerMock = $this->getMock('PHPPdf\Parser\BagContainer', array('getAttributeBag', 'getEnhancementBag'));
        $bagContainerMock->expects($this->once())
                         ->method('getAttributeBag')
                         ->will($this->returnValue($attributeBagMock));
        $bagContainerMock->expects($this->once())
                         ->method('getEnhancementBag')
                         ->will($this->returnValue($enhancementBagMock));


        $stylesheetParserMock = $this->getMock('PHPPdf\Parser\StylesheetParser', array('parse'));
        $stylesheetParserMock->expects($this->once())
                             ->method('parse')
                             ->will($this->returnValue($bagContainerMock));

        $this->parser->setStylesheetParser($stylesheetParserMock);
        
        $glyphFactory = $this->parser->parse($xml);
        $glyph = $glyphFactory->getPrototype('div');

        foreach($attributes as $name => $value)
        {
            $this->assertEquals($value, $glyph->getAttribute($name));
        }

        $this->assertEquals($enhancements, $glyph->getEnhancementsAttributes());
    }

    /**
     * @test
     * @todo formatter class attribute is required
     */
    public function setFormattersNamesForGlyph()
    {
        $xml = <<<XML
<glyphs>
    <glyph name="tag1" class="PHPPdf\Glyph\Container">
        <formatters>
            <formatter class="PHPPdf\Formatter\FloatFormatter" />
        </formatters>
    </glyph>
    <glyph name="tag2" class="PHPPdf\Glyph\Container">
        <formatters>
            <formatter class="PHPPdf\Formatter\FloatFormatter" />
        </formatters>
    </glyph>
</glyphs>
XML;
        $glyphFactory = $this->parser->parse($xml);

        foreach(array('tag1', 'tag2') as $tag)
        {
            $glyph = $glyphFactory->getPrototype($tag);

            $this->assertEquals(array('PHPPdf\Formatter\FloatFormatter'), $glyph->getFormattersNames());
        }
    }
    
    /**
     * @test
     */
    public function setInvocationMethodsOnCreateForFactory()
    {
        $xml = <<<XML
<glyphs>
	<glyph name="tag" class="PHPPdf\Glyph\Container">
		<invoke method="setMarginLeft" argId="marginLeft" />
	</glyph>
</glyphs>
XML;
        $glyphFactory = $this->parser->parse($xml);
        
        $this->assertEquals(array('tag' => array('setMarginLeft' => 'marginLeft')), $glyphFactory->invocationsMethodsOnCreate());
    }
}