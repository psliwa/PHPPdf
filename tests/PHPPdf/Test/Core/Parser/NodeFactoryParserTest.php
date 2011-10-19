<?php

namespace PHPPdf\Test\Core\Parser;

use PHPPdf\Core\Parser\NodeFactoryParser,
    PHPPdf\Core\Parser\StylesheetParser,
    PHPPdf\Core\Node\NodeFactory;

class NodeFactoryParserTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $parser;

    public function setUp()
    {
        $this->parser = new NodeFactoryParser();
    }

    /**
     * @test
     */
    public function parseValidEmptyXml()
    {
        $xml = '<factory></factory>';

        $nodeFactory = $this->parser->parse($xml);

        $this->assertTrue($nodeFactory instanceof NodeFactory);
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
<factory>
    <nodes>
        <node name="div" class="PHPPdf\Core\Node\Container">
        </node>
        <node name="p" class="PHPPdf\Core\Node\Container">
        </node>
    </nodes>
</factory>
XML;
        $nodeFactory = $this->parser->parse($xml);

        $this->assertTrue($nodeFactory->hasPrototype('div'));
        $this->assertTrue($nodeFactory->hasPrototype('p'));
        
        $this->assertFalse($nodeFactory->hasPrototype('anotherTag'));

        $this->assertInstanceOf('PHPPdf\Core\Node\Container', $nodeFactory->getPrototype('div'));
    }
    
    /**
     * @test
     */
    public function setUnitConverter()
    {
        $xml = <<<XML
<factory>
    <nodes>
        <node name="div" class="PHPPdf\Core\Node\Container">
        	<stylesheet>
        		<attribute name="line-height" value="12px" />
        	</stylesheet>
        </node>
    </nodes>
</factory>
XML;
        
        $unitConverter = $this->getMockBuilder('PHPPdf\Core\UnitConverter')
                              ->getMock();

        $expected = 123;
        $unitConverter->expects($this->once())
                      ->method('convertUnit')
                      ->with('12px')
                      ->will($this->returnValue($expected));

        $this->parser->setUnitConverter($unitConverter);
        
        $nodeFactory = $this->parser->parse($xml);
        
        $div = $nodeFactory->getPrototype('div');

        $this->assertEquals($unitConverter, $div->getUnitConverter());
        $this->assertEquals($expected, $div->getAttribute('line-height'));
    }

    /**
     * @test
     * @expectedException PHPPdf\Parser\Exception\ParseException
     */
    public function throwExceptionIfRequiredAttributesAreMissing()
    {
        $xml = <<<XML
<factory>
    <nodes>
        <node name="div">
        </node>
    </nodes>
</factory>
XML;
        $this->parser->parse($xml);
    }

    /**
     * @test
     */
    public function useStylesheetParserForStylesheetParsing()
    {
        $xml = <<<XML
<factory>
    <nodes>
        <node name="div" class="PHPPdf\Core\Node\Container">
            <stylesheet>
            </stylesheet>
        </node>
    </nodes>
</factory>
XML;

        $bagContainerMock = $this->getMock('PHPPdf\Core\Parser\BagContainer', array('apply'));
        $bagContainerMock->expects($this->once())
                         ->method('apply')
                         ->with($this->isInstanceOf('PHPPdf\Core\Node\Container'));

        $stylesheetParserMock = $this->getMock('PHPPdf\Core\Parser\StylesheetParser', array('parse'));
        $stylesheetParserMock->expects($this->once())
                             ->method('parse')
                             ->will($this->returnValue($bagContainerMock));

        $this->parser->setStylesheetParser($stylesheetParserMock);
        
        $nodeFactory = $this->parser->parse($xml);
    }

    /**
     * @test
     * @dataProvider formatterTypeProvider
     * @todo formatter class attribute is required
     */
    public function setFormattersNamesForNode($formatterType)
    {
        $xml = <<<XML
<factory>
    <nodes>
        <node name="tag1" class="PHPPdf\Core\Node\Container">
            <formatters>
                <{$formatterType} class="PHPPdf\Core\Formatter\FloatFormatter" />
            </formatters>
        </node>
        <node name="tag2" class="PHPPdf\Core\Node\Container">
            <formatters>
                <{$formatterType} class="PHPPdf\Core\Formatter\FloatFormatter" />
            </formatters>
        </node>
    </nodes>
</factory>
XML;
        $nodeFactory = $this->parser->parse($xml);

        foreach(array('tag1', 'tag2') as $tag)
        {
            $node = $nodeFactory->getPrototype($tag);

            $this->assertEquals(array('PHPPdf\Core\Formatter\FloatFormatter'), $node->getFormattersNames($formatterType));
        }
    }
    
    public function formatterTypeProvider()
    {
        return array(
            array('pre'),
            array('post'),
        );
    }
    
    /**
     * @test
     */
    public function setInvocationMethodsOnCreateForFactory()
    {
        $xml = <<<XML
<factory>
    <nodes>
    	<node name="tag1" class="PHPPdf\Core\Node\Container">
    		<alias>tag2</alias>
    		<invoke method="setMarginLeft" argId="marginLeft" />
    	</node>
    	<node name="tag3" class="PHPPdf\Core\Node\Container">
    		<invoke method="setMarginLeft" argId="marginLeft2" />
    	</node>
    </nodes>
</factory>
XML;
        $nodeFactory = $this->parser->parse($xml);
        
        $expected = array(
            'tag1' => array('setMarginLeft' => 'marginLeft'),
            'tag3' => array('setMarginLeft' => 'marginLeft2'),
        );

        $this->assertEquals($expected, $nodeFactory->invocationsMethodsOnCreate());
    }
    
    /**
     * @test
     */
    public function registerNodeAliases()
    {
        $xml = <<<XML
<factory>
    <nodes>
    	<node name="tag1" class="PHPPdf\Core\Node\Container">
    		<alias>tag2</alias>
    		<alias>tag3</alias>
    		<invoke method="setMarginLeft" argId="marginLeft" />
    	</node>
    	<node name="tag4" class="PHPPdf\Core\Node\Container">
    		<alias>tag5</alias>
    		<invoke method="setMarginLeft" argId="marginLeft" />
    	</node>
    </nodes>
</factory>
XML;

        $nodeFactory = $this->parser->parse($xml);
        
        $this->assertTrue($nodeFactory->getPrototype('tag1') === $nodeFactory->getPrototype('tag2'));
        $this->assertTrue($nodeFactory->getPrototype('tag1') === $nodeFactory->getPrototype('tag3'));
        $this->assertTrue($nodeFactory->getPrototype('tag4') === $nodeFactory->getPrototype('tag5'));
    }
    
    /**
     * @test
     */
    public function setScalarInvokeArgs()
    {
        $xml = <<<XML
<factory>
	<invoke-args>
		<invoke-arg id="some-id-1" value="someValue-1" />
		<invoke-arg id="some-id-2" value="someValue-2" />
	</invoke-args>
</factory>
XML;

        $nodeFactory = $this->parser->parse($xml);
        
        $this->assertEquals(array('some-id-1' => 'someValue-1', 'some-id-2' => 'someValue-2'), $nodeFactory->getInvokeArgs());
    }
    
    /**
     * @test
     */
    public function setObjectInvokeArgs()
    {
        $xml = <<<XML
<factory>
	<invoke-args>
		<invoke-arg id="some-id" class="stdClass" />
	</invoke-args>
</factory>
XML;

        $nodeFactory = $this->parser->parse($xml);

        $invokeArgs = $nodeFactory->getInvokeArgs();

        $this->assertEquals(1, count($invokeArgs));
        $this->assertInstanceOf('stdClass', $invokeArgs['some-id']);
    }
}