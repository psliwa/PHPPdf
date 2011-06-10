<?php

use PHPPdf\Parser\StylesheetParser,
    PHPPdf\Parser\StylesheetConstraint;

class StylesheetParserTest extends PHPUnit_Framework_TestCase
{
    private $parser;

    public function setUp()
    {
        $this->parser = new StylesheetParser();
    }

    /**
     * @test
     */
    public function parseEmptyXml()
    {
        $xml = '<stylesheet></stylesheet>';

        $constraintContainer = $this->parser->parse($xml);
        $this->assertTrue($constraintContainer instanceof StylesheetConstraint);
        $this->assertEquals(0, count($constraintContainer));
        $this->assertEquals(array(), $constraintContainer->getAttributeBag()->getAll());
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
        $this->assertEquals(array('someName' => 'someValue'), $constraint->getAttributeBag()->getAll());
    }

    private function hasConstraint(StylesheetConstraint $constraint, $tag, array $classes = array())
    {
        return ($this->getConstraint($constraint, $tag, $classes) !== false);
    }

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
        $this->assertEquals(array('someName1' => 'someValue1'), $tag1->getAttributeBag()->getAll());

        $tag2 = $this->getConstraint($tag1, 'tag2');
        $this->assertEquals(0, count($tag2));
        $this->assertEquals(array('someName2' => 'someValue2', 'someName3' => 'someValue3'), $tag2->getAttributeBag()->getAll());

        $tag3 = $this->getConstraint($container, 'tag3');
        $this->assertEquals(0, count($tag3));
        $this->assertEquals(array('someName4' => 'someValue4'), $tag3->getAttributeBag()->getAll());
    }

    /**
     * @test
     */
    public function parseSimpleXmlWithOnlyEnhancements()
    {
        $xml = <<<XML
<stylesheet>
    <tag>
        <enhancement name="border" color="red" type="dotted" />
        <enhancement id="border-2" name="border" color="yellow" type="solid" />
    </tag>
</stylesheet>
XML;

        $container = $this->parser->parse($xml);
        $this->assertEquals(1, count($container));

        $tag = $this->getConstraint($container, 'tag');
        $enhancements = $tag->getEnhancementBag();
        $this->assertEquals(2, count($enhancements));

        $all = array(
            'border' => array('name' => 'border', 'color' => 'red', 'type' => 'dotted'),
            'border-2' => array('name' => 'border', 'color' => 'yellow', 'type' => 'solid'),
        );

        $this->assertEquals($all, $enhancements->getAll());
    }

    /**
     * @test
     * @expectedException PHPPdf\Parser\Exception\ParseException
     */
    public function enhancementNameIsRequired()
    {
        $xml = <<<XML
<stylesheet>
    <tag>
        <enhancement color="red" type="dotted" />
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

        $this->assertEquals(array('name' => $value), $constraint->getAttributeBag()->getAll());
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

        $this->assertEquals(array('someName1' => 'someValue1', 'someName2' => 'someValue2'), $constraint->getAttributeBag()->getAll());
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

        $this->assertEquals(array('someName' => 'value'), $resultConstraint->getAttributeBag()->getAll());
    }
}