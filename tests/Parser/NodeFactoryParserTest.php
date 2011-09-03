<?php

require_once __DIR__.'/../Stub/ClassWithTwoArguments.php';

use PHPPdf\Parser\NodeFactoryParser,
    PHPPdf\Parser\StylesheetParser,
    PHPPdf\Node\Factory as NodeFactory;

class NodeFactoryParserTest extends TestCase
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
        <node name="div" class="PHPPdf\Node\Container">
        </node>
        <node name="p" class="PHPPdf\Node\Container">
        </node>
    </nodes>
</factory>
XML;
        $nodeFactory = $this->parser->parse($xml);

        $this->assertTrue($nodeFactory->hasPrototype('div'));
        $this->assertTrue($nodeFactory->hasPrototype('p'));
        
        $this->assertFalse($nodeFactory->hasPrototype('anotherTag'));

        $this->assertInstanceOf('PHPPdf\Node\Container', $nodeFactory->getPrototype('div'));
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
        <node name="div" class="PHPPdf\Node\Container">
            <stylesheet>
            </stylesheet>
        </node>
    </nodes>
</factory>
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
        
        $nodeFactory = $this->parser->parse($xml);
        $node = $nodeFactory->getPrototype('div');

        foreach($attributes as $name => $value)
        {
            $this->assertEquals($value, $node->getAttribute($name));
        }

        $this->assertEquals($enhancements, $node->getEnhancementsAttributes());
    }

    /**
     * @test
     * @todo formatter class attribute is required
     */
    public function setFormattersNamesForNode()
    {
        $xml = <<<XML
<factory>
    <nodes>
        <node name="tag1" class="PHPPdf\Node\Container">
            <formatters>
                <formatter class="PHPPdf\Formatter\FloatFormatter" />
            </formatters>
        </node>
        <node name="tag2" class="PHPPdf\Node\Container">
            <formatters>
                <formatter class="PHPPdf\Formatter\FloatFormatter" />
            </formatters>
        </node>
    </nodes>
</factory>
XML;
        $nodeFactory = $this->parser->parse($xml);

        foreach(array('tag1', 'tag2') as $tag)
        {
            $node = $nodeFactory->getPrototype($tag);

            $this->assertEquals(array('PHPPdf\Formatter\FloatFormatter'), $node->getFormattersNames());
        }
    }
    
    /**
     * @test
     */
    public function setInvocationMethodsOnCreateForFactory()
    {
        $xml = <<<XML
<factory>
    <nodes>
    	<node name="tag" class="PHPPdf\Node\Container">
    		<invoke method="setMarginLeft" argId="marginLeft" />
    	</node>
    </nodes>
</factory>
XML;
        $nodeFactory = $this->parser->parse($xml);
        
        $this->assertEquals(array('tag' => array('setMarginLeft' => 'marginLeft')), $nodeFactory->invocationsMethodsOnCreate());
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