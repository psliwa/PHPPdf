<?php

namespace PHPPdf\Test\Core\Parser;

use PHPPdf\Core\Parser\BagContainer;

use PHPPdf\Core\Parser\StylesheetParser,
    PHPPdf\Core\Parser\StylesheetConstraint;

class StylesheetParserTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $parser;

    public function setUp()
    {
        $this->parser = new StylesheetParser();
    }

    /**
     * @test
     * @dataProvider emptyXmlProvider
     */
    public function parseEmptyXml($xml)
    {
        $constraintContainer = $this->parser->parse($xml);
        $this->assertTrue($constraintContainer instanceof StylesheetConstraint);
        $this->assertEquals(0, count($constraintContainer));
        $this->assertEquals(array(), $constraintContainer->getAll());
    }
    
    public function emptyXmlProvider()
    {
        return array(
            array('<stylesheet></stylesheet>'),
            array('     <stylesheet></stylesheet>'),
        );
    }

    /**
     * @test
     */
    public function parseSimpleXmlWithOnlyAttributes()
    {
        $xml = <<<XML
<stylesheet>
    <tag>
        <attribute name="someName" value="someValue" />
    </tag>
</stylesheet>
XML;
        $constraintContainer = $this->parser->parse($xml);

        $this->assertEquals(1, count($constraintContainer->count()));
        $this->assertTrue($this->hasConstraint($constraintContainer, 'tag'));
        $constraint = $this->getConstraint($constraintContainer, 'tag');
        $this->assertEquals(array('someName' => 'someValue'), $constraint->getAll());
    }

    private function hasConstraint(StylesheetConstraint $constraint, $tag, array $classes = array())
    {
        return ($this->getConstraint($constraint, $tag, $classes) !== false);
    }

    /**
     * @return StylesheetConstraint
     */
    private function getConstraint(StylesheetConstraint $constraint, $tag, array $classes = array())
    {
        foreach($constraint->getConstraints() as $child)
        {
            $childClasses = $child->getClasses();
            if($child->getTag() == $tag && count(array_intersect($childClasses, $classes)) == count($childClasses))
            {
                return $child;
            }
        }

        return false;
    }

    /**
     * @test
     * @expectedException PHPPdf\Parser\Exception\InvalidTagException
     */
    public function invalidRoot()
    {
        $xml = '<invalid-root></invalid-root>';
        $this->parser->parse($xml);
    }

    /**
     * @test
     */
    public function parseSimpleNeastedXmlWithOnlyAttributes()
    {
        $xml = <<<XML
<stylesheet>
    <tag1>
        <attribute name="someName1" value="someValue1" />
        <tag2>
            <attribute name="someName2" value="someValue2" />
            <attribute name="someName3" value="someValue3" />
        </tag2>
    </tag1>
    <tag3>
        <attribute name="someName4" value="someValue4" />
    </tag3>
</stylesheet>
XML;

        $container = $this->parser->parse($xml);

        $this->assertEquals(2, count($container));
        $this->assertTrue($this->hasConstraint($container, 'tag1'));
        $this->assertTrue($this->hasConstraint($container, 'tag3'));
        $this->assertFalse($this->hasConstraint($container, 'tag2'));

        $tag1 = $this->getConstraint($container, 'tag1');
        $this->assertEquals(1, count($tag1));
        $this->assertTrue($this->hasConstraint($tag1, 'tag2'));
        $this->assertEquals(array('someName1' => 'someValue1'), $tag1->getAll());

        $tag2 = $this->getConstraint($tag1, 'tag2');
        $this->assertEquals(0, count($tag2));
        $this->assertEquals(array('someName2' => 'someValue2', 'someName3' => 'someValue3'), $tag2->getAll());

        $tag3 = $this->getConstraint($container, 'tag3');
        $this->assertEquals(0, count($tag3));
        $this->assertEquals(array('someName4' => 'someValue4'), $tag3->getAll());
    }

    /**
     * @test
     */
    public function parseSimpleXmlWithOnlyComplexAttributes()
    {
        $xml = <<<XML
<stylesheet>
    <tag>
        <complex-attribute name="border" color="red" type="dotted" />
        <enhancement id="border-2" name="border" color="yellow" type="solid" />
    </tag>
</stylesheet>
XML;

        $container = $this->parser->parse($xml);
        $this->assertEquals(1, count($container));

        $tag = $this->getConstraint($container, 'tag');

        $this->assertEquals(2, count($tag->getAll()));

        $all = array(
            'border' => array('name' => 'border', 'color' => 'red', 'type' => 'dotted'),
            'border-2' => array('name' => 'border', 'color' => 'yellow', 'type' => 'solid'),
        );

        $this->assertEquals($all, $tag->getAll());
    }

    /**
     * @test
     * @expectedException PHPPdf\Parser\Exception\ParseException
     */
    public function complexAttributeNameIsRequired()
    {
        $xml = <<<XML
<stylesheet>
    <tag>
        <complex-attribute color="red" type="dotted" />
    </tag>
</stylesheet>
XML;
        $this->parser->parse($xml);
    }

    /**
     * @test
     * @expectedException PHPPdf\Parser\Exception\ParseException
     */
    public function attributeNameIsRequired()
    {
        $xml = <<<XML
<stylesheet>
    <tag>
        <attribute value="value" />
    </tag>
</stylesheet>
XML;
        $this->parser->parse($xml);
    }

    /**
     * @test
     * @expectedException PHPPdf\Parser\Exception\ParseException
     */
    public function attributeValueIsRequired()
    {
        $xml = <<<XML
<stylesheet>
    <tag>
        <attribute name="name" />
    </tag>
</stylesheet>
XML;
        $this->parser->parse($xml);
    }

    /**
     * @test
     * @dataProvider nullifyAttributeValueProvider
     */
    public function nullifyAttributeValueIsAllowed($value)
    {
        $xml = <<<XML
<stylesheet>
    <tag>
        <attribute name="name" value="$value" />
    </tag>
</stylesheet>
XML;
        $container = $this->parser->parse($xml);

        $constraint = $this->getConstraint($container, 'tag');

        $this->assertEquals(array('name' => $value), $constraint->getAll());
    }

    public function nullifyAttributeValueProvider()
    {
        return array(
            array('0'),
            array(''),
        );
    }

    /**
     * @test
     */
    public function tagWithClass()
    {
        $xml = <<<XML
<stylesheet>
    <tag class="class">
        <attribute name="name" value="value" />
    </tag>
</stylesheet>
XML;

        $container = $this->parser->parse($xml);
        $this->assertTrue($this->hasConstraint($container, 'tag', array('class')));
    }

    /**
     * @test
     */
    public function anyTag()
    {
        $xml = <<<XML
<stylesheet>
    <any class="class">
        <attribute name="name" value="value" />
    </any>
</stylesheet>
XML;
        $container = $this->parser->parse($xml);
        $this->assertTrue($this->hasConstraint($container, 'any', array('class')));
    }

    /**
     * @test
     */
    public function parseFromXMLReaderDirectly()
    {
        $xml = <<<XML
<pdf>
    <stylesheet>
        <attribute name="someName1" value="someValue1" />
        <attribute name="someName2" value="someValue2" />
    </stylesheet>
    <tag></tag>
</pdf>
XML;

        $reader = new \XMLReader();
        $reader->XML($xml);
        $reader->read();
        $reader->read();
        $reader->read();
        $reader->read();
        //TODO refactoring

        $constraint = $this->parser->parse($reader);

        $this->assertEquals(array('someName1' => 'someValue1', 'someName2' => 'someValue2'), $constraint->getAll());
        $this->assertEquals(0, count($constraint->getConstraints()));
    }

    /**
     * @test
     */
    public function rootStylesheetConstraintMayByInjected()
    {
        $xml = '<stylesheet></stylesheet>';

        $constraint = new StylesheetConstraint();
        $this->parser->setRoot($constraint);

        $resultConstraint = $this->parser->parse($xml);

        $this->assertTrue($constraint === $resultConstraint);
    }
    
    /**
     * @test
     * @expectedException PHPPdf\Parser\Exception\ParseException
     */
    public function throwExceptionIfUnknownTahHasBeenEncountedInParserSimpleMode()
    {
        $xml = <<<XML
<stylesheet>
	<unknown-tag>
		<attribute name="someName" value="value" />
	</unknown-tag>
</stylesheet>
XML;

        $this->parser->setThrowsExceptionOnConstraintTag(true);
        
        $this->parser->parse($xml);
    }
    
    /**
     * @test
     */
    public function parseFlatXmlStylesheetOnParserSimpleMode()
    {
        $xml = <<<XML
<stylesheet>
	<attribute name="someName" value="value" />
</stylesheet>
XML;

        $this->parser->setThrowsExceptionOnConstraintTag(true);
        
        $resultConstraint = $this->parser->parse($xml);

        $this->assertEquals(array('someName' => 'value'), $resultConstraint->getAll());
    }
    
    /**
     * @test
     */
    public function parseAttributesFromXmlAttributes()
    {
        $xml = <<<XML
<stylesheet>
	<some-tag someAttribute="someValue" />
</stylesheet>
XML;

        $resultConstraint = $this->parser->parse($xml);

        $constraint = $this->getConstraint($resultConstraint, 'some-tag');
        
        $this->assertNotNull($constraint);
        $this->assertEquals(array('someAttribute' => 'someValue'), $constraint->getAll());
    }
    
    /**
     * @test
     */
    public function parseComplexAttributesFromXmlAttributes()
    {
        $xml = <<<XML
<stylesheet>
	<some-tag someComplexAttribute.attribute="value" />
</stylesheet>
XML;

        $complexAttributeFactory = $this->getMockBuilder('PHPPdf\Core\ComplexAttribute\ComplexAttributeFactory')
                                   ->setMethods(array('getDefinitionNames'))
                                   ->disableOriginalConstructor()
                                   ->getMock();
        
        $this->parser->setComplexAttributeFactory($complexAttributeFactory);
        
        $complexAttributeFactory->expects($this->atLeastOnce())
                           ->method('getDefinitionNames')
                           ->will($this->returnValue('someComplexAttribute'));

        $resultConstraint = $this->parser->parse($xml);        

        $constraint = $this->getConstraint($resultConstraint, 'some-tag');
        
        $this->assertNotEquals(false, $constraint);
        $this->assertEquals(array('someComplexAttribute' => array('name' => 'someComplexAttribute', 'attribute' => 'value')), $constraint->getAll());
    }
    
    /**
     * @test
     */
    public function validInterpretSelectorsAsShortTag()
    {
        $xml = <<<XML
<stylesheet>
	<some-tag attribute1="value1" />
	<another-tag attribute2="value2"></another-tag>
</stylesheet>
XML;

        $resultConstraint = $this->parser->parse($xml);

        $constraint = $this->getConstraint($resultConstraint, 'another-tag');

        $this->assertNotEquals(false, $constraint);
        $this->assertEquals(array('attribute2' => 'value2'), $constraint->getAll());
    }
    
    /**
     * @test
     */
    public function addConstraintsFromAttributes()
    {
        $xml = <<<XML
<tag attribute1="value1" attribute2.property1="value2" attribute2.property2="value3" attribute3="value4" attribute4-property3="value5" attribute5-property4="value6" attribute6="value7"></tag>
XML;

        $reader = new \XMLReader();
        $reader->XML($xml);
        $reader->next();
        
        $complexAttributeFactory = $this->getMockBuilder('PHPPdf\Core\ComplexAttribute\ComplexAttributeFactory')
                                   ->setMethods(array('getDefinitionNames'))
                                   ->disableOriginalConstructor()
                                   ->getMock();
                                   
        $this->parser->setComplexAttributeFactory($complexAttributeFactory);
        
        $complexAttributeFactory->expects($this->atLeastOnce())
                           ->method('getDefinitionNames')
                           ->will($this->returnValue(array('attribute2', 'attribute4', 'attribute6')));
                           
        $constraint = new BagContainer();
        
        $this->parser->addConstraintsFromAttributes($constraint, $reader);
        
        $expectedAttributes = array('attribute1' => 'value1', 'attribute2' => array('name' => 'attribute2', 'property1' => 'value2', 'property2' => 'value3'), 'attribute3' => 'value4', 'attribute4' => array('name' => 'attribute4', 'property3' => 'value5'), 'attribute5-property4' => 'value6', 'attribute6' => 'value7');
        
        $this->assertEquals($expectedAttributes, $constraint->getAll());
    }
    
    /**
     * @test
     */
    public function addMultipleAttributesFromStyleAttribute()
    {
        $xml = <<<XML
<tag style="attribute1: value1; attribute2: value2; attribute3-property: value3; attribute4.property: value4;"></tag>
XML;

        $reader = new \XMLReader();
        $reader->XML($xml);
        $reader->next();
        
        $complexAttributeFactory = $this->getMockBuilder('PHPPdf\Core\ComplexAttribute\ComplexAttributeFactory')
                                   ->setMethods(array('getDefinitionNames'))
                                   ->disableOriginalConstructor()
                                   ->getMock();
                                   
        $this->parser->setComplexAttributeFactory($complexAttributeFactory);
        
        $complexAttributeFactory->expects($this->atLeastOnce())
                           ->method('getDefinitionNames')
                           ->will($this->returnValue(array('attribute3', 'attribute4')));
                           
        $constraint = new BagContainer();
        
        $this->parser->addConstraintsFromAttributes($constraint, $reader);
        
        $expectedAttributes = array('attribute1' => 'value1', 'attribute2' => 'value2', 'attribute3' => array('name' => 'attribute3', 'property' => 'value3'), 'attribute4' => array('name' => 'attribute4', 'property' => 'value4'));
        
        $this->assertEquals($expectedAttributes, $constraint->getAll());
    }
}