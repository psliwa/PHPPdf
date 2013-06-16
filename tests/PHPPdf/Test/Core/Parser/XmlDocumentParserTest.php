<?php

namespace PHPPdf\Test\Core\Parser;

use PHPPdf\Core\Node\Container;
use PHPPdf\Core\Node\Paragraph;
use PHPPdf\Core\Node\Text;
use PHPPdf\Core\Parser\XmlDocumentParser,
    PHPPdf\Core\Node\NodeFactory,
    PHPPdf\Core\ComplexAttribute\ComplexAttributeFactory,
    PHPPdf\Core\Node\PageCollection,
    PHPPdf\Core\Parser\StylesheetConstraint;

class XmlDocumentParserTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $parser;
    private $documentMock;
    private $complexAttributeFactoryMock;

    public function setUp()
    {
        $this->documentMock = $this->getMockBuilder('PHPPdf\Core\Document')
                                   ->disableOriginalConstructor()
                                   ->setMethods(array('setMetadataValue'))
                                   ->disableOriginalConstructor()
                                   ->getMock();
        
        $this->complexAttributeFactoryMock = $this->getMock('PHPPdf\Core\ComplexAttribute\ComplexAttributeFactory', array('create', 'getDefinitionNames'));

        $this->parser = new XmlDocumentParser($this->complexAttributeFactoryMock, $this->documentMock);
    }

    /**
     * @test
     */
    public function settingAndGettingProperties()
    {
        $factory = new NodeFactory();

        $this->assertTrue($this->parser->getNodeFactory() instanceof NodeFactory);
        
        $this->parser->setNodeFactory($factory);

        $this->assertTrue($factory === $this->parser->getNodeFactory());
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

        $nodeMock = $this->getNodeMock();
        $mocks = array(array($tag, $nodeMock));
        $factoryMock = $this->getNodeFactoryMock($mocks);

        $this->parser->setNodeFactory($factoryMock);

        $pageCollection = $this->parser->parse($xml);

        $this->assertTrue($pageCollection instanceof PageCollection);

        $nodes = $pageCollection->getChildren();

        $this->assertEquals(1, count($nodes));
        $this->assertTrue($nodeMock === current($nodes));
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
            array('      '.$xml),
            array($reader),
        );
    }
    
    private function getNodeFactoryMock(array $mocks = array(), $indexStep = 1, $excatly = false)
    {
        $factoryMock = $this->getMock('PHPPdf\Core\Node\NodeFactory', array('create'));
        
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

    private function getNodeMock(array $attributes = array(), $baseClass = 'PHPPdf\Core\Node\Page', $methods = array(), $setParentExpectation = true)
    {
        $nodeMock = $this->createNodeMock($baseClass, $methods, $setParentExpectation);
        $this->addNodeAttributesExpectations($nodeMock, $attributes);

        return $nodeMock;
    }

    private function createNodeMock($baseClass = 'PHPPdf\Core\Node\Page', $methods = array(), $setParentExpectation = true)
    {
        $nodeMock = $this->getMock($baseClass, array_merge(array('setParent', 'setAttribute'), $methods));
        if($setParentExpectation)
        {
            $nodeMock->expects($this->once())
                      ->method('setParent');
        }

        return $nodeMock;
    }

    private function addNodeAttributesExpectations($node, $attributes, $attributeStartIndex = 0)
    {
        $index = $attributeStartIndex;
        foreach($attributes as $name => $value)
        {
            $node->expects($this->at($index++))
                      ->method('setAttribute')
                      ->with($this->equalTo($name), $this->equalTo($value))
                      ->will($this->returnValue($node));
        }
    }

    /**
     * @test
     */
    public function parseSingleElementWithAttributes()
    {
        $xml = '<pdf><tag someName="someValue" anotherName="anotherValue" /></pdf>';

        $nodeMock = $this->getNodeMock(array('someName' => 'someValue', 'anotherName' => 'anotherValue'));

        $mocks = array(array('tag', $nodeMock));
        $factoryMock = $this->getNodeFactoryMock($mocks);

        $this->parser->setNodeFactory($factoryMock);

        $pageCollection = $this->parser->parse($xml);

        $nodes = $pageCollection->getChildren();

        $this->assertEquals(1, count($nodes));
        $this->assertTrue($nodeMock === current($nodes));
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
        $nodeMock1 = $this->getNodeMock(array('someName' => 'someValue'));
        $nodeMock2 = $this->getNodeMock(array('anotherName' => 'anotherValue'));

        $mocks = array(array('tag1', $nodeMock1), array('tag2', $nodeMock2));
        $factoryMock = $this->getNodeFactoryMock($mocks);

        $this->parser->setNodeFactory($factoryMock);
        
        $pageCollection = $this->parser->parse($xml);

        $this->assertOnlyChild($nodeMock1, $pageCollection);
        $this->assertOnlyChild($nodeMock2, $nodeMock1);

    }

    private function assertOnlyChild($expectedChild, $parentNode)
    {
        $nodes = $parentNode->getChildren();

        $this->assertEquals(1, count($nodes));
        $this->assertTrue($expectedChild === current($nodes));
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
        $nodeMock = $this->getNodeMock();
        $textMock = $this->getNodeMock(array(), 'PHPPdf\Core\Node\Text', array('setText', 'getText'));
        $paragraphMock = $this->getNodeMock(array(), 'PHPPdf\Core\Node\Paragraph');

        $textMock->expects($this->atLeastOnce())
                 ->method('setText')
                 ->with($this->stringContains('Some text', false))
                 ->will($this->returnValue($textMock));
        $textMock->expects($this->atLeastOnce())
                 ->method('getText')
                 ->will($this->returnValue('        Some text'));

        $mocks = array(array('tag', $nodeMock), array('paragraph', $paragraphMock), array('text', $textMock));
        $factoryMock = $this->getNodeFactoryMock($mocks);

        $this->parser->setNodeFactory($factoryMock);

        $pageCollection = $this->parser->parse($xml);

        $this->assertOnlyChild($nodeMock, $pageCollection);
        $this->assertOnlyChild($paragraphMock, $nodeMock);
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

        $tag1Mock = $this->getNodeMock();
        $tag2Mock = $this->getNodeMock();
        $paragraph1Mock = $this->getNodeMock(array(), 'PHPPdf\Core\Node\Paragraph');
        $text1Mock = $this->getNodeMock(array(), 'PHPPdf\Core\Node\Text', array('setText'));
        $paragraph2Mock = $this->getNodeMock(array(), 'PHPPdf\Core\Node\Paragraph');
        $text2Mock = $this->getNodeMock(array(), 'PHPPdf\Core\Node\Text', array('setText'));
        
        $mocks = array(array('tag1', $tag1Mock), array('paragraph', $paragraph1Mock), array('text', $text1Mock), array('tag2', $tag2Mock), array('paragraph', $paragraph2Mock), array('text', $text2Mock));
        
        $factoryMock = $this->getNodeFactoryMock($mocks);

        $this->parser->setNodeFactory($factoryMock);

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
        $paragraphMock = $this->getNodeMock(array(), 'PHPPdf\Core\Node\Paragraph');
        $text1Mock = $this->getNodeMock(array(), 'PHPPdf\Core\Node\Text', array('setText'));
        $text2Mock = $this->getNodeMock(array(), 'PHPPdf\Core\Node\Text', array('setText'));
        $text3Mock = $this->getNodeMock(array(), 'PHPPdf\Core\Node\Text', array('setText'), false);
        
        $mocks = array(array('paragraph', $paragraphMock), array('text', $text1Mock), array('span', $text2Mock), array('text', $text3Mock));
        
        $factoryMock = $this->getNodeFactoryMock($mocks);

        $this->parser->setNodeFactory($factoryMock);

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
    <tag id="node">
        <tag extends="node" />
    </tag>
</pdf>
XML;
        $nodeMock1 = $this->getNodeMock(array(), 'PHPPdf\Core\Node\Page', array('copy'));

        $nodeMock1->expects($this->never())
                   ->method('setAttribute');

        $nodeMock2 = $this->getNodeMock();

        $nodeMock2->expects($this->never())
                   ->method('setAttribute');
        
        $nodeMock1->expects($this->once())
                   ->method('copy')
                   ->will($this->returnValue($nodeMock2));

        $mocks = array(array('tag', $nodeMock1));
        $factoryMock = $this->getNodeFactoryMock($mocks);

        $this->parser->setNodeFactory($factoryMock);

        $pageCollection = $this->parser->parse($xml);
    }

    /**
     * @test
     * @expectedException \PHPPdf\Core\Parser\Exception\DuplicatedIdException
     */
    public function idMustBeUnique()
    {
        $xml = <<<XML
<pdf>
    <tag1 id="node">
        <tag2 id="node" />
    </tag1>
</pdf>
XML;
        $nodeMock1 = $this->getNodeMock(array(), 'PHPPdf\Core\Node\Page', array(), false);
        $nodeMock2 = $this->getNodeMock(array(), 'PHPPdf\Core\Node\Page', array(), false);
        
        $mocks = array(array('tag1', $nodeMock1), array('tag2', $nodeMock2));
        $factoryMock = $this->getNodeFactoryMock($mocks);

        $this->parser->setNodeFactory($factoryMock);

        $this->parser->parse($xml);
    }

    /**
     * @test
     * @expectedException \PHPPdf\Core\Parser\Exception\IdNotFoundException
     */
    public function extendsAfterUnexistedIdIsForbidden()
    {
        $xml = '<pdf><tag extends="id" /></pdf>';

        $factoryMock = $this->getNodeFactoryMock();

        $this->parser->setNodeFactory($factoryMock);

        $this->parser->parse($xml);
    }

    /**
     * @test
     */
    public function childrenArentInheritedFromNode()
    {
        $xml = <<<XML
<pdf>
    <tag1 id="id">
        <tag2 />
    </tag1>
    <tag1 extends="id" />
</pdf>
XML;

        $nodeMock1 = $this->getNodeMock(array(), 'PHPPdf\Core\Node\Page', array('copy'));
        $nodeMock2 = $this->getNodeMock(array(), 'PHPPdf\Core\Node\Page', array('removeAll'));
        $nodeMock3 = $this->getNodeMock();

        $nodeMock1->expects($this->once())
                   ->method('copy')
                   ->will($this->returnValue($nodeMock2));

        $nodeMock2->expects($this->once())
                   ->method('removeAll');

        $mocks = array(array('tag1', $nodeMock1), array('tag2', $nodeMock3));
        $factoryMock = $this->getNodeFactoryMock($mocks);

        $this->parser->setNodeFactory($factoryMock);

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
            <complex-attribute name="someName" attribute="value" />
        </stylesheet>
    </tag>
</pdf>
XML;
        $reader = new \XMLReader();
        $reader->XML($xml);
        $reader->read();
        $reader->read();

        $constraintMock = $this->getMock('PHPPdf\Core\Parser\StylesheetConstraint', array('apply'));
        $constraintMock->expects($this->once())
                       ->method('apply')
                       ->with($this->isInstanceOf('PHPPdf\Core\Node\Page'));

        $parserMock = $this->getMock('PHPPdf\Core\Parser\StylesheetParser', array('parse'));
        $parserMock->expects($this->once())
                   ->method('parse') 
                   //move after stylesheet close tag and return constraint                  
                   ->will($this->returnCompose(array(
                       $this->returnCallback(function() use($reader){                           
                           while($reader->name != XmlDocumentParser::STYLESHEET_TAG)
                           {
                               $reader->next();
                           }
                       }), $this->returnValue($constraintMock)
                   )));


        $nodeMock = $this->createNodeMock('PHPPdf\Core\Node\Page', array('mergeComplexAttributes'));

        $nodeFactoryMock = $this->getNodeFactoryMock(array(array('tag', $nodeMock)));

        $this->parser->setStylesheetParser($parserMock);
        $this->parser->setNodeFactory($nodeFactoryMock);

        $pageCollection = $this->parser->parse($reader);
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

        $constraintMock = $this->getMock('PHPPdf\Core\Parser\StylesheetConstraint', array('find'));
        $bagContainerMock1 = $this->getBagContainerMock(array('someName1' => 'someValue1'));
        $bagContainerMock2 = $this->getBagContainerMock(array('someName4' => array('someAttribute1' => 'someValue1')));
        $bagContainerMock3 = $this->getBagContainerMock(array('someName2' => 'someValue2', 'someName3' => array('someAttribute2' => 'someValue2')));

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

        $nodeMock1 = $this->getNodeMock(array('someName1' => 'someValue1'), 'PHPPdf\Core\Node\Page', array('mergeComplexAttributes'));
        $nodeMock2 = $this->getNodeMock(array(), 'PHPPdf\Core\Node\Page', array('mergeComplexAttributes'));
        $nodeMock3 = $this->getNodeMock(array('someName2' => 'someValue2'), 'PHPPdf\Core\Node\Page', array('mergeComplexAttributes'));

        $nodeMock1->expects($this->never())
                   ->method('mergeComplexAttributes');

        $this->addComplexAttributeExpectationToNodeMock($nodeMock2, array('someName4' => array('someAttribute1' => 'someValue1')), 0);
        $this->addComplexAttributeExpectationToNodeMock($nodeMock3, array('someName3' => array('someAttribute2' => 'someValue2')), 1);

        $mocks = array(array('tag1', $nodeMock1), array('tag2', $nodeMock2), array('tag3', $nodeMock3));
        $nodeFactoryMock = $this->getNodeFactoryMock($mocks);

        $this->parser->setNodeFactory($nodeFactoryMock);

        $this->parser->parse($xml, $constraintMock);
    }

    private function getBagContainerMock(array $attributes = array(), array $complexAttributes = array())
    {
        $attributes = array_merge($attributes, $complexAttributes);

        $mock = $this->getMock('PHPPdf\Core\Parser\BagContainer', array('getAll'));
        $mock->expects($this->once())
             ->method('getAll')
             ->will($this->returnValue($attributes));
        return $mock;
    }

    private function addExpectationToStylesheetConstraint($constraint, $at, $query, $bagContainerMock)
    {
        $constraint->expects($this->at($at))
                       ->method('find')
                       ->with($this->equalTo($query))
                       ->will($this->returnValue($bagContainerMock));
    }

    private function addComplexAttributeExpectationToNodeMock($node, $complexAttributes, $initSequence)
    {
        foreach($complexAttributes as $name => $parameters)
        {
            $node->expects($this->at($initSequence++))
                  ->method('mergeComplexAttributes')
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
        $placeholderMock1 = $this->getMock('PHPPdf\Core\Node\Container', array('getHeight'));
        $placeholderMock2 = $this->getMock('PHPPdf\Core\Node\Container', array('getHeight'));

        $nodeMock = $this->getMock('PHPPdf\Core\Node\Container', array('hasPlaceholder', 'setPlaceholder'));

        $nodeMock->expects($this->at(0))
                  ->method('hasPlaceholder')
                  ->with($this->equalTo('placeholder'))
                  ->will($this->returnValue(true));
        
        $nodeMock->expects($this->at(1))
                  ->method('setPlaceholder')
                  ->with($this->equalTo('placeholder'), $this->equalTo($placeholderMock1));

        $mocks = array(array('tag1', $nodeMock), array('tag2', $placeholderMock1), array('tag3', $placeholderMock2));
        $nodeFactoryMock = $this->getNodeFactoryMock($mocks);

        $this->parser->setNodeFactory($nodeFactoryMock);

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

        $nodeMock = $this->getMock('PHPPdf\Core\Node\Container', array('setAttribute', 'setParent'));
        $nodeMock->expects($this->once())
                  ->method('setAttribute')
                  ->id('attribute')
                  ->with('someAttribute', 'someValue');
        $nodeMock->expects($this->once())
                  ->method('setParent')
                  ->after('attribute');


        $nodeFactoryMock = $this->getNodeFactoryMock(array(array('tag1', $nodeMock)));

        $this->parser->setNodeFactory($nodeFactoryMock);

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
    public function readComplexAttributesInAttributeStyle()
    {
        $xml = <<<XML
<pdf>
	<tag someAttribute="someValue" someComplexAttribute.property="propertyValue"></tag>
</pdf>
XML;

        $nodeMock = $this->getMock('PHPPdf\Core\Node\Container', array('setAttribute', 'mergeComplexAttributes'));
        $nodeMock->expects($this->once())
                  ->method('setAttribute')
                  ->id('attribute')
                  ->with('someAttribute', 'someValue');
        $nodeMock->expects($this->once())
                  ->method('mergeComplexAttributes')
                  ->with('someComplexAttribute', array('name' => 'someComplexAttribute', 'property' => 'propertyValue'));

        $nodeFactoryMock = $this->getNodeFactoryMock(array(array('tag', $nodeMock)));
        
        $this->complexAttributeFactoryMock->expects($this->atLeastOnce())
                                     ->method('getDefinitionNames')
                                     ->will($this->returnValue(array('someComplexAttribute')));

        $this->parser->setNodeFactory($nodeFactoryMock);

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
        $tag1NodeMock = $this->getMock('PHPPdf\Core\Node\Container', array('setPriorityFromParent', 'setAttribute'));
        $tag2NodeMock = $this->getMock('PHPPdf\Core\Node\Container', array('setPriorityFromParent', 'setAttribute'));
        
        $mocks = array(array('tag1', $tag1NodeMock), array('tag2', $tag2NodeMock));
        $nodeFactoryMock = $this->getNodeFactoryMock($mocks);
        
        $this->parser->setNodeFactory($nodeFactoryMock);

        $pages = $this->parser->parse($xml);
        
        $this->assertEquals(2, count($pages->getChildren()));
        $children = $pages->getChildren();
        $this->assertTrue($tag1NodeMock === $children[0]);
        $this->assertTrue($tag2NodeMock === $children[1]);
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
        $textNode = $this->getMock('PHPPdf\Core\Node\Text', array('setPriorityFromParent'));
        $paragraphNode = $this->getMock('PHPPdf\Core\Node\Paragraph', array('setPriorityFromParent'));
        
        $nodeFactoryMock = $this->getNodeFactoryMock(array(array('paragraph', $paragraphNode), array('text', $textNode)));
        
        $this->parser->setNodeFactory($nodeFactoryMock);
        
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

        $textNodeTag1 = $this->getMock('PHPPdf\Core\Node\Text', array('setPriorityFromParent'));
        $textNodeSpace = $this->getMock('PHPPdf\Core\Node\Text', array('setPriorityFromParent', 'setText', 'getText'));
        $paragraphNode = $this->getMock('PHPPdf\Core\Node\Paragraph', array('setPriorityFromParent'));
        $textNodeTag2 = $this->getMock('PHPPdf\Core\Node\Text', array('setPriorityFromParent'));
        
        $textNodeSpace->expects($this->atLeastOnce())
                       ->method('setText')
                       ->with(' ');
        $textNodeSpace->expects($this->atLeastOnce())
                       ->method('getText')
                       ->will($this->returnValue(' '));
        
        $nodeFactoryMock = $this->getNodeFactoryMock(array(array('tag1', $textNodeTag1), array('paragraph', $paragraphNode), array('text', $textNodeSpace), array('tag2', $textNodeTag2)));
        
        $this->parser->setNodeFactory($nodeFactoryMock);
        
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

        $text1Node = $this->getMock('PHPPdf\Core\Node\Text', array('setPriorityFromParent'));
        $text2Node = $this->getMock('PHPPdf\Core\Node\Text', array('setPriorityFromParent'));
        $tag1Node = $this->getMock('PHPPdf\Core\Node\Container', array('setPriorityFromParent'));
        $paragraph1Node = $this->getMock('PHPPdf\Core\Node\Paragraph', array('setPriorityFromParent'));
        $paragraph2Node = $this->getMock('PHPPdf\Core\Node\Paragraph', array('setPriorityFromParent'));
        
        $nodeFactoryMock = $this->getNodeFactoryMock(array(array('tag1', $tag1Node), array('text1', $text1Node), array('paragraph', $paragraph1Node), array('text2', $text2Node), array('paragraph', $paragraph2Node)));
        
        $this->parser->setNodeFactory($nodeFactoryMock);
        
        $pages = $this->parser->parse($xml);
        
        $this->assertEquals(2, count($pages->getChildren()));
        
        $this->assertInstanceOf('PHPPdf\Core\Node\Container', $pages->getChild(0));
        $this->assertInstanceOf('PHPPdf\Core\Node\Paragraph', $pages->getChild(1));
    }
    
    /**
     * @test
     */
    public function dontTrimLastSpaceOfTextIfNextElementAlsoIsTextNode()
    {
        $xml = <<<XML
<pdf>
	Some text <text1>another text</text1>
</pdf>
XML;

        $text1Node = new Text();
        $text2Node = new Text();
        $text3Node = new Text();
        $paragraphNode = new Paragraph();
        
        $nodeFactoryMock = $this->getNodeFactoryMock(array(array('paragraph', $paragraphNode), array('text', $text1Node), array('text1', $text2Node), array('text', $text3Node)));
        
        $this->parser->setNodeFactory($nodeFactoryMock);
        
        $pages = $this->parser->parse($xml);
        
        foreach(array($text1Node, $text2Node) as $textNode)
        {
            $textNode->preFormat($this->documentMock);
        }

        $this->assertEquals('another text', $text2Node->getText());
        $this->assertEquals('Some text ', $text1Node->getText());
    }
    
    /**
     * @test
     */
    public function zeroStrindIsNotTreatedAsEmptyString()
    {
        $xml = <<<XML
<pdf>0</pdf>
XML;
        $textNode = new Text();
        $paragraphNode = new Paragraph();
        
        $nodeFactoryMock = $this->getNodeFactoryMock(array(array('paragraph', $paragraphNode), array('text', $textNode)));
        
        $this->parser->setNodeFactory($nodeFactoryMock);
        
        $pages = $this->parser->parse($xml);
        $this->assertEquals('0', $textNode->getText());
    }
    
    /**
     * @test
     * @dataProvider recogniceBahaviourAttributeProvider
     */
    public function recognizeBehaviourAttribute($behaviourValue)
    {
        $xml = <<<XML
<pdf>
	<tag behaviour="$behaviourValue" />
</pdf>
XML;

        $behaviour = $this->getMockBuilder('PHPPdf\Core\Node\Behaviour\Behaviour')
                          ->setMethods(array('doAttach', 'attach'))
                          ->getMock();
        $behavoiurFactory = $this->getMockBuilder('PHPPdf\Core\Node\Behaviour\Factory')
                                 ->setMethods(array('create', 'getSupportedBehaviourNames'))
                                 ->getMock();
                                 
        $behavoiurFactory->expects($this->atLeastOnce())
                         ->method('getSupportedBehaviourNames')
                         ->will($this->returnValue(array('behaviour')));
        $behavoiurFactory->expects($this->once())
                         ->method('create')
                         ->with('behaviour', $behaviourValue)
                         ->will($this->returnValue($behaviour));
        
        $node = $this->getNodeMock(array(), 'PHPPdf\Core\Node\Container', array('addBehaviour'));
        $node->expects($this->once())
              ->method('addBehaviour')
              ->with($behaviour);
        $nodeFactoryMock = $this->getNodeFactoryMock(array(array('tag', $node)));
        
        $this->parser->setBehaviourFactory($behavoiurFactory);
        $this->parser->setNodeFactory($nodeFactoryMock);
        
        $this->parser->parse($xml);
    }
    
    public function recogniceBahaviourAttributeProvider()
    {
        return array(
            array('arg'),
            //utf-8 chars are valid parsed in xml attributes?
            array('ąęść'),
        );
    }
    

    /**
     * @test
     */
    public function parseBehaviours()
    {
        $xml = <<<XML
<pdf>
    <tag1>
        <behaviours>
            <note>some text 1</note>
            <bookmark option1="value1" option2="value2">some text 2</bookmark>
        </behaviours>
    </tag1>
</pdf>
XML;
        $nodeMock = $this->getMock('PHPPdf\Core\Node\Container', array('addBehaviour'));

        $mocks = array(array('tag1', $nodeMock));
        $nodeFactoryMock = $this->getNodeFactoryMock($mocks);
        $behaviourFactoryMock = $this->getMockBuilder('PHPPdf\Core\Node\Behaviour\Factory')
                                     ->setMethods(array('getSupportedBehaviourNames', 'create'))
                                     ->getMock();

        $args = array('some text 1', 'some text 2');
        $behaviourNames = array('note', 'bookmark');
        $behaviourOptions = array(array(), array('option1' => 'value1', 'option2' => 'value2'));
        
        $behaviourFactoryMock->expects($this->atLeastOnce())
                             ->method('getSupportedBehaviourNames')
                             ->will($this->returnValue($behaviourNames));
        
        //first two invocations are getSupportedBehaviourNames method calls
        $behaviourFactoryCallIndex = 2;

        foreach($behaviourNames as $i => $behaviourName)
        {
            $behaviour = $this->getMockBuilder('PHPPdf\Core\Node\Behaviour\Behaviour')
                              ->setMethods(array('doAttach'))
                              ->getMock();
            $matcher = $behaviourFactoryMock->expects($this->at($behaviourFactoryCallIndex))
                                             ->method('create')
                                             ->with($behaviourName, $args[$i], $behaviourOptions[$i])
                                             ->will($this->returnValue($behaviour));
                                             
            $nodeMock->expects($this->at($i))
                      ->method('addBehaviour')
                      ->with($behaviour);
            $behaviourFactoryCallIndex++;
        }
                                     
        $this->parser->setBehaviourFactory($behaviourFactoryMock);
        $this->parser->setNodeFactory($nodeFactoryMock);

        $this->parser->parse($xml);
    }
    
    /**
     * @test
     */
    public function parseMetadataFromDocumentRoot()
    {
        $xml = <<<XML
<pdf Subject="some subject" Title="some title">
</pdf>
XML;

        $this->documentMock->expects($this->at(0))
                           ->method('setMetadataValue')
                           ->with('Subject', 'some subject');
        $this->documentMock->expects($this->at(1))
                           ->method('setMetadataValue')
                           ->with('Title', 'some title');
        
        $this->parser->parse($xml);
    }
    
    /**
     * @test
     * @expectedException PHPPdf\Parser\Exception\ParseException
     */
    public function throwExceptionOnParseError()
    {
        $warningEnabled = \PHPUnit_Framework_Error_Warning::$enabled;
        \PHPUnit_Framework_Error_Warning::$enabled = false;
        
        $xml = <<<XML
<pdf></pdfaa>    
XML;
        try
        {
            $this->parser->parse($xml);
        }
        catch(\Exception $e)
        {
            \PHPUnit_Framework_Error_Warning::$enabled = $warningEnabled;
            throw $e;
        }
    }
    
    /**
     * @test
     */
    public function notifyParserListeners()
    {
        $xml = <<<XML
<pdf>
	<tag1>
		<tag2 />
	</tag1>
</pdf>
XML;
        $node1 = $this->getMock('PHPPdf\Core\Node\Container', array('setPriorityFromParent'));
        $node2 = $this->getMock('PHPPdf\Core\Node\Container', array('setPriorityFromParent'));
        
        $nodeFactoryMock = $this->getNodeFactoryMock(array(array('tag1', $node1), array('tag2', $node2)));
        
        $listener = $this->getMock('PHPPdf\Core\Parser\DocumentParserListener');
        
        $listener->expects($this->at(0))
                 ->method('onStartParseNode')
                 ->with($this->documentMock, $this->isInstanceOf('PHPPdf\Core\Node\PageCollection'), $node1);
        $listener->expects($this->at(1))
                 ->method('onStartParseNode')
                 ->with($this->documentMock, $this->isInstanceOf('PHPPdf\Core\Node\PageCollection'), $node2);
        $listener->expects($this->at(2))
                 ->method('onEndParseNode')
                 ->with($this->documentMock, $this->isInstanceOf('PHPPdf\Core\Node\PageCollection'), $node2);
        $listener->expects($this->at(3))
                 ->method('onEndParseNode')
                 ->with($this->documentMock, $this->isInstanceOf('PHPPdf\Core\Node\PageCollection'), $node1);
        $listener->expects($this->at(4))
                 ->method('onEndParsing')
                 ->with($this->documentMock, $this->isInstanceOf('PHPPdf\Core\Node\PageCollection'));
        
        $this->parser->setNodeFactory($nodeFactoryMock);
        $this->parser->addListener($listener);
        
        $this->parser->parse($xml);
    }
    
    /**
     * @test
     */
    public function notifyParserListenersOnParagraph()
    {
        $xml = <<<XML
<pdf>
	Some text
</pdf>
XML;

        $node = new Text();
        $paragraph = new Paragraph();
        
        $nodeFactoryMock = $this->getNodeFactoryMock(array(array('paragraph', $paragraph), array('text', $node)));
        
        $listener = $this->getMock('PHPPdf\Core\Parser\DocumentParserListener');
        
        $listener->expects($this->at(0))
                 ->method('onStartParseNode')
                 ->with($this->documentMock, $this->isInstanceOf('PHPPdf\Core\Node\PageCollection'), $paragraph);
        $listener->expects($this->at(1))
                 ->method('onStartParseNode')
                 ->with($this->documentMock, $this->isInstanceOf('PHPPdf\Core\Node\PageCollection'), $node);
        $listener->expects($this->at(2))
                 ->method('onEndParseNode')
                 ->with($this->documentMock, $this->isInstanceOf('PHPPdf\Core\Node\PageCollection'), $node);
        $listener->expects($this->at(3))
                 ->method('onEndParseNode')
                 ->with($this->documentMock, $this->isInstanceOf('PHPPdf\Core\Node\PageCollection'), $paragraph);
        $listener->expects($this->at(4))
                 ->method('onEndParsing')
                 ->with($this->documentMock, $this->isInstanceOf('PHPPdf\Core\Node\PageCollection'));
        
        $this->parser->setNodeFactory($nodeFactoryMock);
        $this->parser->addListener($listener);
        
        $this->parser->parse($xml);
    } 
}