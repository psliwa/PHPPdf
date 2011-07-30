<?php

use PHPPdf\Parser\DocumentParser,
    PHPPdf\Glyph\Factory as GlyphFactory,
    PHPPdf\Enhancement\Factory as EnhancementFactory,
    PHPPdf\Glyph\PageCollection,
    PHPPdf\Parser\StylesheetConstraint;

class DocumentParserTest extends TestCase
{
    private $parser;

    public function setUp()
    {
        $this->parser = new DocumentParser();
    }

    /**
     * @test
     */
    public function settingAndGettingProperties()
    {
        $factory = new GlyphFactory();
        $enhancementFactory = new EnhancementFactory();

        $this->assertTrue($this->parser->getGlyphFactory() instanceof GlyphFactory);
        $this->assertTrue($this->parser->getEnhancementFactory() instanceof EnhancementFactory);
        
        $this->parser->setGlyphFactory($factory);
        $this->parser->setEnhancementFactory($enhancementFactory);

        $this->assertTrue($factory === $this->parser->getGlyphFactory());
        $this->assertTrue($enhancementFactory === $this->parser->getEnhancementFactory());
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
    public function validRoot()
    {
        $xml = '<pdf></pdf>';

        $result = $this->parser->parse($xml);

        $this->assertTrue($result instanceof PageCollection);
    }

    /**
     * @test
     * @expectedException PHPPdf\Parser\Exception\ParseException
     */
    public function throwsExceptionIfTagDosntExistsInFactory()
    {
        $xml = '<pdf><tag1 /></pdf>';

        $this->parser->parse($xml);
    }

    /**
     * @test
     * @dataProvider simpleXmlProvider
     */
    public function parseSingleElement($xml)
    {
        $tag = 'tag';

        $glyphMock = $this->getGlyphMock();
        $mocks = array(array($tag, $glyphMock));
        $factoryMock = $this->getGlyphFactoryMock($mocks);

        $this->parser->setGlyphFactory($factoryMock);

        $pageCollection = $this->parser->parse($xml);

        $this->assertTrue($pageCollection instanceof PageCollection);

        $glyphs = $pageCollection->getChildren();

        $this->assertEquals(1, count($glyphs));
        $this->assertTrue($glyphMock === current($glyphs));
    }

    public function simpleXmlProvider()
    {
        $xml = '<pdf><tag /></pdf>';
        $reader = new \XMLReader();
        $reader->XML($xml);
        $reader->read();
        $reader->read();

        return array(
            array($xml),
            array($reader),
        );
    }
    
    private function getGlyphFactoryMock(array $mocks = array(), $indexStep = 1, $excatly = false)
    {
        $factoryMock = $this->getMock('PHPPdf\Glyph\Factory', array('create'));
        
        $index = 0;
        foreach($mocks as $mockData)
        {
            list($tag, $mock) = $mockData;
            
            $expection = $excatly ? $this->exactly($excatly) : $this->at($index);
            $factoryMock->expects($expection)
                        ->method('create')
                        ->with($this->equalTo($tag))
                        ->will($this->returnValue($mock));
            $index += $indexStep;
        }

        return $factoryMock;
    }

    private function getGlyphMock(array $attributes = array(), $baseClass = 'PHPPdf\Glyph\Page', $methods = array(), $setParentExpectation = true)
    {
        $glyphMock = $this->createGlyphMock($baseClass, $methods, $setParentExpectation);
        $this->addGlyphAttributesExpectations($glyphMock, $attributes);

        return $glyphMock;
    }

    private function createGlyphMock($baseClass = 'PHPPdf\Glyph\Page', $methods = array(), $setParentExpectation = true)
    {
        $glyphMock = $this->getMock($baseClass, array_merge(array('setParent', 'setAttribute'), $methods));
        if($setParentExpectation)
        {
            $glyphMock->expects($this->once())
                      ->method('setParent');
        }

        return $glyphMock;
    }

    private function addGlyphAttributesExpectations($glyph, $attributes, $attributeStartIndex = 0)
    {
        $index = $attributeStartIndex;
        foreach($attributes as $name => $value)
        {
            $glyph->expects($this->at($index++))
                      ->method('setAttribute')
                      ->with($this->equalTo($name), $this->equalTo($value))
                      ->will($this->returnValue($glyph));
        }
    }

    /**
     * @test
     */
    public function parseSingleElementWithAttributes()
    {
        $xml = '<pdf><tag someName="someValue" anotherName="anotherValue" /></pdf>';

        $glyphMock = $this->getGlyphMock(array('someName' => 'someValue', 'anotherName' => 'anotherValue'));

        $mocks = array(array('tag', $glyphMock));
        $factoryMock = $this->getGlyphFactoryMock($mocks);

        $this->parser->setGlyphFactory($factoryMock);

        $pageCollection = $this->parser->parse($xml);

        $glyphs = $pageCollection->getChildren();

        $this->assertEquals(1, count($glyphs));
        $this->assertTrue($glyphMock === current($glyphs));
    }

    /**
     * @test
     */
    public function parseNeastedElementsWithAttributes()
    {
        $xml = <<<XML
<pdf>
    <tag1 someName="someValue">
        <tag2 anotherName="anotherValue"></tag2>
    </tag1>
</pdf>
XML;
        $glyphMock1 = $this->getGlyphMock(array('someName' => 'someValue'));
        $glyphMock2 = $this->getGlyphMock(array('anotherName' => 'anotherValue'));

        $mocks = array(array('tag1', $glyphMock1), array('tag2', $glyphMock2));
        $factoryMock = $this->getGlyphFactoryMock($mocks);

        $this->parser->setGlyphFactory($factoryMock);
        
        $pageCollection = $this->parser->parse($xml);

        $this->assertOnlyChild($glyphMock1, $pageCollection);
        $this->assertOnlyChild($glyphMock2, $glyphMock1);

    }

    private function assertOnlyChild($expectedChild, $parentGlyph)
    {
        $glyphs = $parentGlyph->getChildren();

        $this->assertEquals(1, count($glyphs));
        $this->assertTrue($expectedChild === current($glyphs));
    }

    /**
     * @test
     */
    public function parseTextElement()
    {
        $xml = <<<XML
<pdf>
    <tag>
        Some text
    </tag>
</pdf>
XML;
        $glyphMock = $this->getGlyphMock();
        $textMock = $this->getGlyphMock(array(), 'PHPPdf\Glyph\Text', array('setText', 'getText'));
        $paragraphMock = $this->getGlyphMock(array(), 'PHPPdf\Glyph\Paragraph');

        $textMock->expects($this->atLeastOnce())
                 ->method('setText')
                 ->with($this->stringContains('Some text', false))
                 ->will($this->returnValue($textMock));
        $textMock->expects($this->atLeastOnce())
                 ->method('getText')
                 ->will($this->returnValue('        Some text'));

        $mocks = array(array('tag', $glyphMock), array('paragraph', $paragraphMock), array('text', $textMock));
        $factoryMock = $this->getGlyphFactoryMock($mocks);

        $this->parser->setGlyphFactory($factoryMock);

        $pageCollection = $this->parser->parse($xml);

        $this->assertOnlyChild($glyphMock, $pageCollection);
        $this->assertOnlyChild($paragraphMock, $glyphMock);
        $this->assertOnlyChild($textMock, $paragraphMock);
    }
    
    /**
     * @test
     */
    public function createParagraphForEachSingleText()
    {
        $xml = <<<XML
<pdf>
    <tag1>
        Some text
        <tag2></tag2>
        Some text
    </tag1>
</pdf>
XML;

        $tag1Mock = $this->getGlyphMock();
        $tag2Mock = $this->getGlyphMock();
        $paragraph1Mock = $this->getGlyphMock(array(), 'PHPPdf\Glyph\Paragraph');
        $text1Mock = $this->getGlyphMock(array(), 'PHPPdf\Glyph\Text', array('setText'));
        $paragraph2Mock = $this->getGlyphMock(array(), 'PHPPdf\Glyph\Paragraph');
        $text2Mock = $this->getGlyphMock(array(), 'PHPPdf\Glyph\Text', array('setText'));
        
        $mocks = array(array('tag1', $tag1Mock), array('paragraph', $paragraph1Mock), array('text', $text1Mock), array('tag2', $tag2Mock), array('paragraph', $paragraph2Mock), array('text', $text2Mock));
        
        $factoryMock = $this->getGlyphFactoryMock($mocks);

        $this->parser->setGlyphFactory($factoryMock);

        $pageCollection = $this->parser->parse($xml);
    }
    
    /**
     * @test
     */
    public function wrapTwoTextSiblingsIntoTheSameParagraph()
    {
        $xml = <<<XML
<pdf>
    Some text <span>Some another text</span>
</pdf>
XML;
        $paragraphMock = $this->getGlyphMock(array(), 'PHPPdf\Glyph\Paragraph');
        $text1Mock = $this->getGlyphMock(array(), 'PHPPdf\Glyph\Text', array('setText'));
        $text2Mock = $this->getGlyphMock(array(), 'PHPPdf\Glyph\Text', array('setText'));
        $text3Mock = $this->getGlyphMock(array(), 'PHPPdf\Glyph\Text', array('setText'), false);
        
        $mocks = array(array('paragraph', $paragraphMock), array('text', $text1Mock), array('span', $text2Mock), array('text', $text3Mock));
        
        $factoryMock = $this->getGlyphFactoryMock($mocks);

        $this->parser->setGlyphFactory($factoryMock);

        $pages = $this->parser->parse($xml);
        
        $this->assertOnlyChild($paragraphMock, $pages);
        $children = $paragraphMock->getChildren();
        $this->assertEquals(2, count($children));
    }

    /**
     * @test
     */
    public function deepInheritance()
    {
        $xml = <<<XML
<pdf>
    <tag id="glyph">
        <tag extends="glyph" />
    </tag>
</pdf>
XML;
        $glyphMock1 = $this->getGlyphMock(array(), 'PHPPdf\Glyph\Page', array('copy'));

        $glyphMock1->expects($this->never())
                   ->method('setAttribute');

        $glyphMock2 = $this->getGlyphMock();

        $glyphMock2->expects($this->never())
                   ->method('setAttribute');
        
        $glyphMock1->expects($this->once())
                   ->method('copy')
                   ->will($this->returnValue($glyphMock2));

        $mocks = array(array('tag', $glyphMock1));
        $factoryMock = $this->getGlyphFactoryMock($mocks);

        $this->parser->setGlyphFactory($factoryMock);

        $pageCollection = $this->parser->parse($xml);
    }

    /**
     * @test
     * @expectedException \PHPPdf\Parser\Exception\DuplicatedIdException
     */
    public function idMustBeUnique()
    {
        $xml = <<<XML
<pdf>
    <tag1 id="glyph">
        <tag2 id="glyph" />
    </tag1>
</pdf>
XML;
        $glyphMock1 = $this->getGlyphMock(array(), 'PHPPdf\Glyph\Page', array(), false);
        $glyphMock2 = $this->getGlyphMock(array(), 'PHPPdf\Glyph\Page', array(), false);
        
        $mocks = array(array('tag1', $glyphMock1), array('tag2', $glyphMock2));
        $factoryMock = $this->getGlyphFactoryMock($mocks);

        $this->parser->setGlyphFactory($factoryMock);

        $this->parser->parse($xml);
    }

    /**
     * @test
     * @expectedException \PHPPdf\Parser\Exception\IdNotFoundException
     */
    public function extendsAfterUnexistedIdIsForbidden()
    {
        $xml = '<pdf><tag extends="id" /></pdf>';

        $factoryMock = $this->getGlyphFactoryMock();

        $this->parser->setGlyphFactory($factoryMock);

        $this->parser->parse($xml);
    }

    /**
     * @test
     */
    public function childrenArentInheritedFromGlyph()
    {
        $xml = <<<XML
<pdf>
    <tag1 id="id">
        <tag2 />
    </tag1>
    <tag1 extends="id" />
</pdf>
XML;

        $glyphMock1 = $this->getGlyphMock(array(), 'PHPPdf\Glyph\Page', array('copy'));
        $glyphMock2 = $this->getGlyphMock(array(), 'PHPPdf\Glyph\Page', array('removeAll'));
        $glyphMock3 = $this->getGlyphMock();

        $glyphMock1->expects($this->once())
                   ->method('copy')
                   ->will($this->returnValue($glyphMock2));

        $glyphMock2->expects($this->once())
                   ->method('removeAll');

        $mocks = array(array('tag1', $glyphMock1), array('tag2', $glyphMock3));
        $factoryMock = $this->getGlyphFactoryMock($mocks);

        $this->parser->setGlyphFactory($factoryMock);

        $this->parser->parse($xml);
    }

    /**
     * @test
     */
    public function parseAttributeSubDocument()
    {
        $xml = <<<XML
<pdf>
    <tag>
        <stylesheet>
            <attribute someName1="someValue1" />
            <attribute someName2="someValue2" />
            <enhancement name="someName" attribute="value" />
        </stylesheet>
    </tag>
</pdf>
XML;
        $reader = new \XMLReader();
        $reader->XML($xml);
        $reader->read();
        $reader->read();
        
        $attributes = array('someName1' => 'someValue1', 'someName2' => 'someValue2');

        $attributeBagMock = $this->getAttributeBagMock($attributes);
        $enhancementBagMock = $this->getEnhancementBagMock(array('someName' => array('attribute' => 'value')));

        $constraintMock = $this->getMock('PHPPdf\Parser\StylesheetConstraint', array('getAttributeBag', 'getEnhancementBag'));
        $constraintMock->expects($this->once())
                       ->method('getAttributeBag')
                       ->will($this->returnValue($attributeBagMock));

        $constraintMock->expects($this->once())
                       ->method('getEnhancementBag')
                       ->will($this->returnValue($enhancementBagMock));

        $parserMock = $this->getMock('PHPPdf\Parser\StylesheetParser', array('parse'));
        $parserMock->expects($this->once())
                   ->method('parse') 
                   //move after stylesheet close tag and return constraint                  
                   ->will($this->returnCompose(array(
                       $this->returnCallback(function() use($reader){                           
                           while($reader->name != DocumentParser::STYLESHEET_TAG)
                           {
                               $reader->next();
                           }
                       }), $this->returnValue($constraintMock)
                   )));


        $glyphMock= $this->createGlyphMock('PHPPdf\Glyph\Page', array('mergeEnhancementAttributes'));
        $this->addGlyphAttributesExpectations($glyphMock, $attributes, 1);
        $glyphMock->expects($this->at(3))
                  ->method('mergeEnhancementAttributes')
                  ->with($this->equalTo('someName'), $this->equalTo(array('attribute' => 'value')));

        $glyphFactoryMock = $this->getGlyphFactoryMock(array(array('tag', $glyphMock)));

        $this->parser->setStylesheetParser($parserMock);
        $this->parser->setGlyphFactory($glyphFactoryMock);

        $pageCollection = $this->parser->parse($reader);
    }

    private function getAttributeBagMock(array $attributes)
    {
        $attributeBagMock = $this->getMock('PHPPdf\Util\AttributeBag', array('getAll'));
        $attributeBagMock->expects($this->once())
                         ->method('getAll')
                         ->will($this->returnValue($attributes));

        return $attributeBagMock;
    }

    private function getEnhancementBagMock(array $enhancements)
    {
        $enhancementBagMock = $this->getMock('PHPPdf\Enhancement\EnhancementBag', array('getAll'));
        $enhancementBagMock->expects($this->once())
                           ->method('getAll')
                           ->will($this->returnValue($enhancements));

        return $enhancementBagMock;
    }

    private function getEnhancementFactoryMock(array $enhancements, array $enhancementMocks)
    {
        $enhancementFactoryMock = $this->getMock('PHPPdf\Enhancement\Factory', array('create'));

        $i = 0;
        foreach($enhancements as $name => $parameters)
        {
            $enhancementFactoryMock->expects($this->at($i))
                                   ->method('create')
                                   ->with($this->equalTo($name), $this->equalTo($parameters))
                                   ->will($this->returnValue($enhancementMocks[$i]));
            $i++;
        }

        return $enhancementFactoryMock;
    }

    /**
     * @test
     */
    public function useStylesheetConstraintToRetrieveStylesheet()
    {
        $xml = <<<XML
<pdf>
    <tag1></tag1>
    <tag2>
        <tag3 class="class"></tag3>
    </tag2>
</pdf>
XML;

        $constraintMock = $this->getMock('PHPPdf\Parser\StylesheetConstraint', array('find'));
        $bagContainerMock1 = $this->getBagContainerMock(array('someName1' => 'someValue1'));
        $bagContainerMock2 = $this->getBagContainerMock(array(), array('someName1' => array('someAttribute1' => 'someValue1')));
        $bagContainerMock3 = $this->getBagContainerMock(array('someName2' => 'someValue2'), array('someName2' => array('someAttribute2' => 'someValue2')));

        $this->addExpectationToStylesheetConstraint($constraintMock, 0, array(
           array(
               'tag' => 'tag1',
               'classes' => array()
           )
        ), $bagContainerMock1);

        $this->addExpectationToStylesheetConstraint($constraintMock, 1, array(
           array(
               'tag' => 'tag2',
               'classes' => array()
           )
        ), $bagContainerMock2);

        $this->addExpectationToStylesheetConstraint($constraintMock, 2, array(
           array(
               'tag' => 'tag2',
               'classes' => array()
           ), array(
               'tag' => 'tag3',
               'classes' => array('class')
           )
        ), $bagContainerMock3);

        $glyphMock1 = $this->getGlyphMock(array('someName1' => 'someValue1'), 'PHPPdf\Glyph\Page', array('mergeEnhancementAttributes'));
        $glyphMock2 = $this->getGlyphMock(array(), 'PHPPdf\Glyph\Page', array('mergeEnhancementAttributes'));
        $glyphMock3 = $this->getGlyphMock(array('someName2' => 'someValue2'), 'PHPPdf\Glyph\Page', array('mergeEnhancementAttributes'));

        $glyphMock1->expects($this->never())
                   ->method('mergeEnhancementAttributes');

        $this->addEnhancementExpectationToGlyphMock($glyphMock2, array('someName1' => array('someAttribute1' => 'someValue1')), 0);
        $this->addEnhancementExpectationToGlyphMock($glyphMock3, array('someName2' => array('someAttribute2' => 'someValue2')), 1);

        $mocks = array(array('tag1', $glyphMock1), array('tag2', $glyphMock2), array('tag3', $glyphMock3));
        $glyphFactoryMock = $this->getGlyphFactoryMock($mocks);

        $this->parser->setGlyphFactory($glyphFactoryMock);

        $this->parser->parse($xml, $constraintMock);
    }

    private function getBagContainerMock(array $attributes = array(), array $enhancements = array())
    {
        $attributeBagMock = $this->getAttributeBagMock($attributes);
        $enhancementBagMock = $this->getEnhancementBagMock($enhancements);

        $mock = $this->getMock('PHPPdf\Parser\BagContainer', array('getAttributeBag', 'getEnhancementBag'));
        $mock->expects($this->once())
             ->method('getAttributeBag')
             ->will($this->returnValue($attributeBagMock));
        $mock->expects($this->once())
             ->method('getEnhancementBag')
             ->will($this->returnValue($enhancementBagMock));

        return $mock;
    }

    private function addExpectationToStylesheetConstraint($constraint, $at, $query, $bagContainerMock)
    {
        $constraint->expects($this->at($at))
                       ->method('find')
                       ->with($this->equalTo($query))
                       ->will($this->returnValue($bagContainerMock));
    }

    private function addEnhancementExpectationToGlyphMock($glyph, $enhancements, $initSequence)
    {
        foreach($enhancements as $name => $parameters)
        {
            $glyph->expects($this->at($initSequence++))
                  ->method('mergeEnhancementAttributes')
                  ->with($this->equalTo($name), $this->equalTo($parameters));
        }
    }

    /**
     * @test
     */
    public function parsePlaceholders()
    {
        $xml = <<<XML
<pdf>
    <tag1>
        <placeholders>
            <placeholder>
                <tag2>
                    <tag3 />
                </tag2>
            </placeholder>
        </placeholders>
    </tag1>
</pdf>
XML;

        $height = 50;
        $placeholderMock1 = $this->getMock('PHPPdf\Glyph\Container', array('getHeight'));
        $placeholderMock2 = $this->getMock('PHPPdf\Glyph\Container', array('getHeight'));

        $glyphMock = $this->getMock('PHPPdf\Glyph\Container', array('hasPlaceholder', 'setPlaceholder'));

        $glyphMock->expects($this->at(0))
                  ->method('hasPlaceholder')
                  ->with($this->equalTo('placeholder'))
                  ->will($this->returnValue(true));
        
        $glyphMock->expects($this->at(1))
                  ->method('setPlaceholder')
                  ->with($this->equalTo('placeholder'), $this->equalTo($placeholderMock1));

        $mocks = array(array('tag1', $glyphMock), array('tag2', $placeholderMock1), array('tag3', $placeholderMock2));
        $glyphFactoryMock = $this->getGlyphFactoryMock($mocks);

        $this->parser->setGlyphFactory($glyphFactoryMock);

        $this->parser->parse($xml);
    }

    /**
     * @test
     */
    public function setAttributesBeforeParent()
    {
        $xml = <<<XML
<pdf>
    <tag1 someAttribute="someValue"></tag1>
</pdf>
XML;

        $glyphMock = $this->getMock('PHPPdf\Glyph\Container', array('setAttribute', 'setParent'));
        $glyphMock->expects($this->once())
                  ->method('setAttribute')
                  ->id('attribute')
                  ->with('someAttribute', 'someValue');
        $glyphMock->expects($this->once())
                  ->method('setParent')
                  ->after('attribute');


        $glyphFactoryMock = $this->getGlyphFactoryMock(array(array('tag1', $glyphMock)));

        $this->parser->setGlyphFactory($glyphFactoryMock);

        $this->parser->parse($xml);
    }
    
    /**
     * @test
     * @dataProvider unknownTagProvider
     * @expectedException \PHPPdf\Parser\Exception\ParseException
     */
    public function throwParseExceptionOnUnknownTag($unknownTag)
    {
        $xml = <<<XML
<pdf>
    <{$unknownTag} someAttribute="someValue"></{$unknownTag}>
</pdf>
XML;
        $this->parser->parse($xml);
    }
    
    public function unknownTagProvider()
    {
        return array(
            array('some-tag'),
            array('attribute'),
            array('enhancement'),
        );
    }
    
    /**
     * @test
     */
    public function readEnhancementsInAttributeStyle()
    {
        $xml = <<<XML
<pdf>
	<tag someAttribute="someValue" someEnhancement.property="propertyValue"></tag>
</pdf>
XML;

        $glyphMock = $this->getMock('PHPPdf\Glyph\Container', array('setAttribute', 'mergeEnhancementAttributes'));
        $glyphMock->expects($this->once())
                  ->method('setAttribute')
                  ->id('attribute')
                  ->with('someAttribute', 'someValue');
        $glyphMock->expects($this->once())
                  ->method('mergeEnhancementAttributes')
                  ->with('someEnhancement', array('name' => 'someEnhancement', 'property' => 'propertyValue'));

        $glyphFactoryMock = $this->getGlyphFactoryMock(array(array('tag', $glyphMock)));

        $this->parser->setGlyphFactory($glyphFactoryMock);

        $this->parser->parse($xml);
    }
    
    /**
     * @test
     */
    public function allowShortTagsWithAttributes()
    {
        $xml = <<<XML
<pdf>
	<tag1 attribute="value" />
	<tag2></tag2>
</pdf>
XML;
        $tag1GlyphMock = $this->getMock('PHPPdf\Glyph\Container', array('setPriorityFromParent', 'setAttribute'));
        $tag2GlyphMock = $this->getMock('PHPPdf\Glyph\Container', array('setPriorityFromParent', 'setAttribute'));
        
        $mocks = array(array('tag1', $tag1GlyphMock), array('tag2', $tag2GlyphMock));
        $glyphFactoryMock = $this->getGlyphFactoryMock($mocks);
        
        $this->parser->setGlyphFactory($glyphFactoryMock);

        $pages = $this->parser->parse($xml);
        
        $this->assertEquals(2, count($pages->getChildren()));
        $children = $pages->getChildren();
        $this->assertTrue($tag1GlyphMock === $children[0]);
        $this->assertTrue($tag2GlyphMock === $children[1]);
    }
    
    /**
     * @test
     */
    public function wrapTextIntoParagraphObject()
    {
        $xml = <<<XML
<pdf>
	Some text
</pdf>
XML;
        $textGlyph = $this->getMock('PHPPdf\Glyph\Text', array('setPriorityFromParent'));
        $paragraphGlyph = $this->getMock('PHPPdf\Glyph\Paragraph', array('setPriorityFromParent'));
        
        $glyphFactoryMock = $this->getGlyphFactoryMock(array(array('paragraph', $paragraphGlyph), array('text', $textGlyph)));
        
        $this->parser->setGlyphFactory($glyphFactoryMock);
        
        $pages = $this->parser->parse($xml);
        
        $children = $pages->getChildren();
        $this->assertEquals(1, count($children));
    }
    
    /**
     * @test
     */
    public function parseSignificantWhitespaces()
    {
        $xml = <<<XML
<pdf>
<tag1></tag1> <tag2></tag2>
</pdf>
XML;

        $textGlyphTag1 = $this->getMock('PHPPdf\Glyph\Text', array('setPriorityFromParent'));
        $textGlyphSpace = $this->getMock('PHPPdf\Glyph\Text', array('setPriorityFromParent', 'setText', 'getText'));
        $paragraphGlyph = $this->getMock('PHPPdf\Glyph\Paragraph', array('setPriorityFromParent'));
        $textGlyphTag2 = $this->getMock('PHPPdf\Glyph\Text', array('setPriorityFromParent'));
        
        $textGlyphSpace->expects($this->atLeastOnce())
                       ->method('setText')
                       ->with(' ');
        $textGlyphSpace->expects($this->atLeastOnce())
                       ->method('getText')
                       ->will($this->returnValue(' '));
        
        $glyphFactoryMock = $this->getGlyphFactoryMock(array(array('tag1', $textGlyphTag1), array('paragraph', $paragraphGlyph), array('text', $textGlyphSpace), array('tag2', $textGlyphTag2)));
        
        $this->parser->setGlyphFactory($glyphFactoryMock);
        
        $pages = $this->parser->parse($xml);
    }
    
    /**
     * @test
     */
    public function validInterpretationOfParagraphs()
    {
        $xml = <<<XML
<pdf>
	<tag1><text1></text1></tag1>
	<text2></text2>
</pdf>
XML;

        $text1Glyph = $this->getMock('PHPPdf\Glyph\Text', array('setPriorityFromParent'));
        $text2Glyph = $this->getMock('PHPPdf\Glyph\Text', array('setPriorityFromParent'));
        $tag1Glyph = $this->getMock('PHPPdf\Glyph\Container', array('setPriorityFromParent'));
        $paragraph1Glyph = $this->getMock('PHPPdf\Glyph\Paragraph', array('setPriorityFromParent'));
        $paragraph2Glyph = $this->getMock('PHPPdf\Glyph\Paragraph', array('setPriorityFromParent'));
        
        $glyphFactoryMock = $this->getGlyphFactoryMock(array(array('tag1', $tag1Glyph), array('text1', $text1Glyph), array('paragraph', $paragraph1Glyph), array('text2', $text2Glyph), array('paragraph', $paragraph2Glyph)));
        
        $this->parser->setGlyphFactory($glyphFactoryMock);
        
        $pages = $this->parser->parse($xml);
        
        $this->assertEquals(2, count($pages->getChildren()));
        
        $this->assertInstanceOf('PHPPdf\Glyph\Container', $pages->getChild(0));
        $this->assertInstanceOf('PHPPdf\Glyph\Paragraph', $pages->getChild(1));
    }
}